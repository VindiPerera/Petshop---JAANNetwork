<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $stock = Stock::where('branch_id', $branchId)->pluck('quantity', 'product_id');

        $products = Product::with('category')->where('is_active', true)->orderBy('name')->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'sku' => $p->sku, 'name' => $p->name, 'category' => $p->category?->name,
                'price' => (float) $p->sale_price, 'stock' => (int) ($stock[$p->id] ?? 0),
            ])->values();

        return view('pos', [
            'products' => $products,
            'customers' => Customer::where('branch_id', $branchId)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        $user = $request->user();

        $sale = DB::transaction(function () use ($data, $user) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
            $subtotal = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $products[$i['product_id']]->sale_price);
            $discount = min((float) ($data['discount'] ?? 0), $subtotal);
            $total = $subtotal - $discount;

            abort_if($data['paid'] < $total, 422, 'Amount paid is less than the total.');

            $sale = Sale::create([
                'invoice_no' => (string) Str::uuid(),
                'branch_id' => $user->branch_id, 'customer_id' => $data['customer_id'] ?? null, 'user_id' => $user->id,
                'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total,
                'paid' => $data['paid'], 'payment_method' => $data['payment_method'],
            ]);

            $sale->update(['invoice_no' => 'INV-'.$user->branch->code.'-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($data['items'] as $row) {
                $p = $products[$row['product_id']];
                $sale->items()->create([
                    'product_id' => $p->id, 'quantity' => $row['quantity'], 'unit_price' => $p->sale_price,
                    'unit_cost' => $p->cost_price, 'line_total' => $row['quantity'] * $p->sale_price,
                ]);
                StockService::change($user->branch_id, $p->id, -$row['quantity'], 'sale', $sale->invoice_no, $user->id);
            }
            return $sale;
        });

        return response()->json(['ok' => true, 'url' => route('sales.show', $sale)]);
    }
}
