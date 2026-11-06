<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $sales = Sale::with('branch', 'customer', 'user')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->q, fn ($q, $s) => $q->where('invoice_no', 'like', "%$s%"))
            ->latest()->paginate(25)->withQueryString();
        return view('sales.index', ['sales' => $sales, 'branches' => $this->branchOptions()]);
    }

    public function show(Request $request, Sale $sale)
    {
        abort_unless($request->user()->isAdmin() || $sale->branch_id === $request->user()->branch_id, 403);
        $sale->load('branch', 'customer', 'user', 'items.product', 'payments');
        return view('sales.show', compact('sale'));
    }
}
