<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\Exporter;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);

        // Only products that exist in a location's stock list (i.e. were received there) are shown.
        $query = Stock::with('product.category', 'branch')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('branches', 'branches.id', '=', 'stocks.branch_id')
            ->select('stocks.*')
            ->when($branchId, fn ($q) => $q->where('stocks.branch_id', $branchId))
            ->when($request->category_id, fn ($q, $c) => $q->where('products.category_id', $c))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('products.name', 'like', "%$s%")->orWhere('products.sku', 'like', "%$s%")))
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stocks.quantity', '<=', 'products.reorder_level'))
            ->when($request->boolean('in_stock'), fn ($q) => $q->where('stocks.quantity', '>', 0))
            ->orderBy('branches.name')->orderBy('products.name');

        if ($fmt = $this->exportFormat($request)) {
            $rows = $query->get();
            $sections = [[
                'heading' => 'Inventory',
                'columns' => ['Location', 'SKU', 'Product', 'Category', 'Qty', 'Reorder level', 'Cost value', 'Retail value'],
                'align' => ['left', 'left', 'left', 'left', 'right', 'right', 'right', 'right'],
                'rows' => $rows->map(fn ($s) => [
                    $s->branch->name, $s->product->sku, $s->product->name, $s->product->category?->name ?? '',
                    (int) $s->quantity, (int) $s->product->reorder_level,
                    (float) ($s->quantity * $s->product->cost_price), (float) ($s->quantity * $s->product->sale_price),
                ])->all(),
                'totals' => ['Total', '', '', '', (int) $rows->sum('quantity'), '',
                    (float) $rows->sum(fn ($s) => $s->quantity * $s->product->cost_price),
                    (float) $rows->sum(fn ($s) => $s->quantity * $s->product->sale_price)],
            ]];
            $where = $branchId ? Branch::find($branchId)?->name : 'All locations';
            return Exporter::download($fmt, 'Inventory Report', $where.' · as at '.now()->format('Y-m-d H:i'), $sections, 'inventory-report-'.now()->format('Y-m-d'));
        }

        return view('inventory.index', [
            'stocks' => $query->paginate(30)->withQueryString(),
            'branches' => $this->branchOptions(), 'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function movements(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $movements = StockMovement::with('product', 'branch', 'user')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()->paginate(40)->withQueryString();
        return view('inventory.movements', ['movements' => $movements, 'branches' => $this->branchOptions()]);
    }
}
