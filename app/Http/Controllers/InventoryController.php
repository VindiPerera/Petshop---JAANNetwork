<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $stocks = Stock::with('product.category', 'branch')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->select('stocks.*')
            ->when($branchId, fn ($q) => $q->where('stocks.branch_id', $branchId))
            ->when($request->category_id, fn ($q, $c) => $q->where('products.category_id', $c))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('products.name', 'like', "%$s%")->orWhere('products.sku', 'like', "%$s%")))
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stocks.quantity', '<=', 'products.reorder_level'))
            ->orderBy('products.name')->paginate(30)->withQueryString();

        return view('inventory.index', [
            'stocks' => $stocks, 'branches' => $this->branchOptions(), 'categories' => Category::orderBy('name')->get(),
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
