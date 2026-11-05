<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Sales summary: by day, by branch, top products, by payment method. */
    public function sales(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $base = fn () => DB::table('sales')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to);

        return view('reports.sales', [
            'from' => $from, 'to' => $to, 'branches' => $this->branchOptions(),
            'summary' => $base()->selectRaw('COUNT(*) as bills, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discounts')->first(),
            'daily' => $base()->selectRaw('DATE(sales.created_at) as day, COUNT(*) as bills, SUM(total) as revenue')->groupBy('day')->orderBy('day')->get(),
            'byBranch' => $base()->join('branches', 'branches.id', '=', 'sales.branch_id')
                ->selectRaw('branches.name, COUNT(*) as bills, SUM(sales.total) as revenue')->groupBy('branches.id', 'branches.name')->orderByDesc('revenue')->get(),
            'byPayment' => $base()->selectRaw('payment_method, COUNT(*) as bills, SUM(total) as revenue')->groupBy('payment_method')->get(),
            'topProducts' => DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
                ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to)
                ->selectRaw('products.name, products.sku, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total) as revenue')
                ->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc('qty')->limit(10)->get(),
        ]);
    }

    /** Profit = revenue - cost of goods sold (cost captured at sale time), per branch. */
    public function profit(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $rows = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.created_at', '>=', $from)->whereDate('sales.created_at', '<=', $to)
            ->selectRaw('branches.name, SUM(sale_items.line_total) as revenue, SUM(sale_items.quantity * sale_items.unit_cost) as cost')
            ->groupBy('branches.id', 'branches.name')->get();

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

        return view('reports.stock_value', ['rows' => $rows, 'branches' => $this->branchOptions()]);
    }
}
