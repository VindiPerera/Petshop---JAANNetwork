<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $today = Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereDate('created_at', today());
        $month = Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('created_at', '>=', now()->startOfMonth());

        $lowStock = Stock::join('products', 'products.id', '=', 'stocks.product_id')
            ->join('branches', 'branches.id', '=', 'stocks.branch_id')
            ->when($branchId, fn ($q) => $q->where('stocks.branch_id', $branchId))
            ->whereColumn('stocks.quantity', '<=', 'products.reorder_level')
            ->where('products.is_active', true)
            ->select('products.name', 'products.sku', 'branches.name as branch', 'stocks.quantity', 'products.reorder_level')
            ->orderBy('stocks.quantity')->limit(10)->get();

        $data = [
            'todayTotal' => (clone $today)->sum('total'),
            'todayCount' => (clone $today)->count(),
            'monthTotal' => $month->sum('total'),
            'lowStock' => $lowStock,
            'recent' => Sale::with('branch', 'customer')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->latest()->limit(8)->get(),
        ];

        if ($request->user()->isAdmin()) {
            $data['branchSales'] = Branch::shops()->get()->map(fn ($b) => [
                'branch' => $b,
                'today' => Sale::where('branch_id', $b->id)->whereDate('created_at', today())->sum('total'),
                'month' => Sale::where('branch_id', $b->id)->where('created_at', '>=', now()->startOfMonth())->sum('total'),
                'stock_value' => (float) DB::table('stocks')->join('products', 'products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', $b->id)->sum(DB::raw('stocks.quantity * products.cost_price')),
            ]);
            $data['warehouseValue'] = (float) DB::table('stocks')->join('products', 'products.id', '=', 'stocks.product_id')
                ->where('stocks.branch_id', Branch::warehouse()->id)->sum(DB::raw('stocks.quantity * products.cost_price'));
        }

        return view('dashboard', $data);
    }
}
