<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Receive goods from a supplier into the central warehouse. */
class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier', 'user')->latest()->paginate(20);
        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('admin.purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'received_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($data, $request) {
            $warehouse = Branch::warehouse();
            $purchase = Purchase::create([
                'purchase_no' => (string) Str::uuid(),
                'supplier_id' => $data['supplier_id'] ?? null, 'user_id' => $request->user()->id,
                'received_date' => $data['received_date'], 'note' => $data['note'] ?? null,
            ]);
            $purchase->update(['purchase_no' => 'PUR-'.str_pad($purchase->id, 6, '0', STR_PAD_LEFT)]);
            $total = 0;
            foreach ($data['items'] as $row) {
                $purchase->items()->create($row);
                $total += $row['quantity'] * $row['unit_cost'];
                StockService::change($warehouse->id, $row['product_id'], $row['quantity'], 'purchase', $purchase->purchase_no, $request->user()->id);
                Product::whereKey($row['product_id'])->update(['cost_price' => $row['unit_cost']]);
            }
            $purchase->update(['total_cost' => $total]);
            return $purchase;
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('ok', 'Stock received into warehouse.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'user', 'items.product');
        return view('admin.purchases.show', compact('purchase'));
    }
}
