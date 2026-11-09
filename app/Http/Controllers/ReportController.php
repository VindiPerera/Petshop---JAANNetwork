<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\Exporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Sales summary: by day, by branch, top products, by payment method. */
    public function sales(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        [$from, $to] = $this->range($request);

        $base = fn () => DB::table('sales')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to);

        $summary = $base()->selectRaw('COUNT(*) as bills, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discounts')->first();
        $daily = $base()->selectRaw('DATE(sales.created_at) as day, COUNT(*) as bills, SUM(total) as revenue')->groupBy('day')->orderBy('day')->get();
        $byBranch = $base()->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->selectRaw('branches.name, COUNT(*) as bills, SUM(sales.total) as revenue')->groupBy('branches.id', 'branches.name')->orderByDesc('revenue')->get();
        $byChannel = $base()->selectRaw('sales.channel, COUNT(*) as bills, SUM(sales.total) as revenue, SUM(sales.delivery_fee) as delivery')
            ->groupBy('sales.channel')->orderBy('sales.channel')->get();
        // Online orders placed in the period, by status (billed ones are also in the sales figures above).
        $ordersByStatus = DB::table('orders')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->selectRaw('status, COUNT(*) as orders, SUM(total) as value')->groupBy('status')->get();
        $byPayment = DB::table('sale_payments')->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to)
            ->selectRaw('sale_payments.method as payment_method, COUNT(DISTINCT sales.id) as bills, SUM(sale_payments.amount) as revenue')
            ->groupBy('sale_payments.method')->get();
        $topProducts = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to)
            ->selectRaw('products.name, products.sku, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total) as revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc('qty')->limit(10)->get();

        if ($fmt = $this->exportFormat($request)) {
            $invoices = $base()->join('branches', 'branches.id', '=', 'sales.branch_id')
                ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
                ->selectRaw('sales.invoice_no, sales.channel, sales.created_at, branches.name as branch, customers.name as customer, sales.payment_method, sales.discount, sales.total')
                ->orderBy('sales.created_at')->limit(5000)->get();

            $sections = [
                ['heading' => 'Summary', 'columns' => ['Revenue', 'Bills', 'Discounts given'], 'align' => ['right', 'right', 'right'],
                 'rows' => [[(float) $summary->revenue, (int) $summary->bills, (float) $summary->discounts]]],
                ['heading' => 'Sales by branch', 'columns' => ['Branch', 'Bills', 'Revenue'], 'align' => ['left', 'right', 'right'],
                 'rows' => $byBranch->map(fn ($r) => [$r->name, (int) $r->bills, (float) $r->revenue])->all()],
                ['heading' => 'Sales by channel', 'columns' => ['Channel', 'Bills', 'Revenue'], 'align' => ['left', 'right', 'right'],
                 'rows' => $byChannel->map(fn ($r) => [$r->channel === 'online' ? 'Online orders' : 'POS (in shop)', (int) $r->bills, (float) $r->revenue])->all()],
                ['heading' => 'Online orders by status', 'columns' => ['Status', 'Orders', 'Value'], 'align' => ['left', 'right', 'right'],
                 'rows' => $ordersByStatus->map(fn ($r) => [ucfirst($r->status), (int) $r->orders, (float) $r->value])->all()],
                ['heading' => 'Sales by payment method', 'columns' => ['Method', 'Bills', 'Amount'], 'align' => ['left', 'right', 'right'],
                 'rows' => $byPayment->map(fn ($r) => [ucfirst($r->payment_method), (int) $r->bills, (float) $r->revenue])->all()],
                ['heading' => 'Top products', 'columns' => ['SKU', 'Product', 'Qty', 'Revenue'], 'align' => ['left', 'left', 'right', 'right'],
                 'rows' => $topProducts->map(fn ($r) => [$r->sku, $r->name, (int) $r->qty, (float) $r->revenue])->all()],
                ['heading' => 'Daily sales', 'columns' => ['Date', 'Bills', 'Revenue'], 'align' => ['left', 'right', 'right'],
                 'rows' => $daily->map(fn ($r) => [$r->day, (int) $r->bills, (float) $r->revenue])->all(),
                 'totals' => ['Total', (int) $summary->bills, (float) $summary->revenue]],
                ['heading' => 'All invoices', 'columns' => ['Invoice', 'Channel', 'Date', 'Branch', 'Customer', 'Payment', 'Discount', 'Total'],
                 'align' => ['left', 'left', 'left', 'left', 'left', 'left', 'right', 'right'],
                 'rows' => $invoices->map(fn ($r) => [$r->invoice_no, $r->channel === 'online' ? 'Online' : 'POS', substr($r->created_at, 0, 16), $r->branch, $r->customer ?? 'Walk-in', ucfirst($r->payment_method), (float) $r->discount, (float) $r->total])->all()],
            ];
            return Exporter::download($fmt, 'Sales Report', $this->subtitle($branchId, $from, $to), $sections, "sales-report-$from-to-$to");
        }

        return view('reports.sales', [
            'from' => $from, 'to' => $to, 'branches' => $this->branchOptions(),
            'summary' => $summary, 'daily' => $daily, 'byBranch' => $byBranch, 'byPayment' => $byPayment, 'topProducts' => $topProducts,
            'byChannel' => $byChannel, 'ordersByStatus' => $ordersByStatus,
        ]);
    }

    /** Profit = revenue - cost of goods sold (cost captured at sale time), per branch. */
    public function profit(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        [$from, $to] = $this->range($request);

        $rows = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to)
            ->selectRaw('branches.name, SUM(sale_items.line_total) as revenue, SUM(sale_items.quantity * sale_items.unit_cost) as cost')
            ->groupBy('branches.id', 'branches.name')->get();

        if ($fmt = $this->exportFormat($request)) {
            $rev = (float) $rows->sum('revenue'); $cost = (float) $rows->sum('cost');
            $sections = [[
                'heading' => 'Profit by branch', 'columns' => ['Branch', 'Revenue', 'Cost of goods', 'Gross profit', 'Margin %'],
                'align' => ['left', 'right', 'right', 'right', 'right'],
                'rows' => $rows->map(fn ($r) => [$r->name, (float) $r->revenue, (float) $r->cost, (float) ($r->revenue - $r->cost), $r->revenue > 0 ? round(($r->revenue - $r->cost) / $r->revenue * 100, 1) : 0.0])->all(),
                'totals' => ['Total', $rev, $cost, $rev - $cost, $rev > 0 ? round(($rev - $cost) / $rev * 100, 1) : 0.0],
            ]];
            return Exporter::download($fmt, 'Profit Report', $this->subtitle($branchId, $from, $to), $sections, "profit-report-$from-to-$to");
        }

        return view('reports.profit', ['rows' => $rows, 'from' => $from, 'to' => $to, 'branches' => $this->branchOptions()]);
    }

    /** Stock valuation per location (admin sees all locations incl. warehouse). */
    public function stockValue(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $rows = DB::table('stocks')->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('branches', 'branches.id', '=', 'stocks.branch_id')
            ->when($branchId, fn ($q) => $q->where('stocks.branch_id', $branchId))
            ->selectRaw('branches.name, branches.type, SUM(stocks.quantity) as units, SUM(stocks.quantity * products.cost_price) as cost_value, SUM(stocks.quantity * products.sale_price) as retail_value')
            ->groupBy('branches.id', 'branches.name', 'branches.type')->get();

        if ($fmt = $this->exportFormat($request)) {
            $sections = [[
                'heading' => 'Stock value by location', 'columns' => ['Location', 'Type', 'Units', 'Value at cost', 'Value at retail'],
                'align' => ['left', 'left', 'right', 'right', 'right'],
                'rows' => $rows->map(fn ($r) => [$r->name, ucfirst($r->type), (int) $r->units, (float) $r->cost_value, (float) $r->retail_value])->all(),
                'totals' => ['Total', '', (int) $rows->sum('units'), (float) $rows->sum('cost_value'), (float) $rows->sum('retail_value')],
            ]];
            return Exporter::download($fmt, 'Stock Value Report', $this->subtitle($branchId, null, null), $sections, 'stock-value-report-'.now()->format('Y-m-d'));
        }

        return view('reports.stock_value', ['rows' => $rows, 'branches' => $this->branchOptions()]);
    }

    /** @return array{0:string,1:string} [from, to] as Y-m-d, defaulting to this month. */
    private function range(Request $request): array
    {
        $valid = fn ($d) => $d && strtotime($d) ? date('Y-m-d', strtotime($d)) : null;
        return [
            $valid($request->input('from')) ?? now()->startOfMonth()->toDateString(),
            $valid($request->input('to')) ?? now()->toDateString(),
        ];
    }

    private function subtitle(?int $branchId, ?string $from, ?string $to): string
    {
        $where = $branchId ? Branch::find($branchId)?->name : 'All branches';
        return $where.($from ? " · $from to $to" : ' · as at '.now()->format('Y-m-d H:i'));
    }
}
