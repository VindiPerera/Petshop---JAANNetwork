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
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $user->branch_id;

        // Only products this branch actually holds (received through a GRN) and that are in stock.
        $stock = Stock::where('branch_id', $branchId)->where('quantity', '>', 0)->pluck('quantity', 'product_id');

        $products = Product::with('category')->where('is_active', true)->whereIn('id', $stock->keys())->orderBy('name')->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'sku' => $p->sku, 'barcode' => (string) $p->barcode, 'name' => $p->name, 'category' => $p->category?->name,
                'price' => (float) $p->sale_price, 'stock' => (int) $stock[$p->id],
            ])->values();

        return view('pos', [
            'products' => $products,
            'branchInfo' => $user->branch->only('name', 'address', 'phone', 'email', 'tax_no', 'currency', 'receipt_footer') + ['logo' => $user->branch->logo_url],
            'cashier' => $user->name,
            'customers' => Customer::where('branch_id', $branchId)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);
        $user = $request->user();

        $sale = DB::transaction(function () use ($data, $user) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

            // Per-line totals, each line discount capped at the line value.
            $lines = collect($data['items'])->map(function ($i) use ($products) {
                $gross = $i['quantity'] * $products[$i['product_id']]->sale_price;
                $disc = min((float) ($i['discount'] ?? 0), $gross);
                return $i + ['gross' => $gross, 'line_discount' => $disc, 'net' => $gross - $disc];
            });

            $lineNet = round($lines->sum('net'), 2);
            $extraDiscount = min((float) ($data['discount'] ?? 0), $lineNet);
            $total = round($lineNet - $extraDiscount, 2);
            $totalDiscount = round($lines->sum('line_discount') + $extraDiscount, 2);

            // Payments: change can only be given out of cash.
            $payments = collect($data['payments']);
            $tendered = round($payments->sum('amount'), 2);
            $cash = round($payments->where('method', 'cash')->sum('amount'), 2);
            $nonCash = round($tendered - $cash, 2);
            if ($tendered < $total) {
                throw ValidationException::withMessages(['payments' => 'Amount paid is less than the total.']);
            }
            if ($nonCash > $total) {
                throw ValidationException::withMessages(['payments' => 'Card/transfer amounts cannot exceed the total.']);
            }
            $change = round($tendered - $total, 2);
            if ($change > $cash) {
                throw ValidationException::withMessages(['payments' => 'Change can only be given from the cash amount.']);
            }

            $methods = $payments->pluck('method')->unique();
            $sale = Sale::create([
                'invoice_no' => (string) Str::uuid(),
                'branch_id' => $user->branch_id, 'customer_id' => $data['customer_id'] ?? null, 'user_id' => $user->id,
                'subtotal' => round($lines->sum('gross'), 2), 'discount' => $totalDiscount, 'total' => $total,
                'paid' => $tendered, 'payment_method' => $methods->count() > 1 ? 'split' : $methods->first(),
            ]);
            $sale->update(['invoice_no' => 'INV-'.$user->branch->code.'-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT)]);

            // Store payments net of change so payment-method reports add up to the sale total.
            $remainingChange = $change;
            foreach ($payments as $p) {
                $amount = (float) $p['amount'];
                if ($p['method'] === 'cash' && $remainingChange > 0) {
                    $take = min($remainingChange, $amount);
                    $amount -= $take;
                    $remainingChange -= $take;
                }
                if ($amount > 0) {
                    $sale->payments()->create(['method' => $p['method'], 'amount' => $amount]);
                }
            }

            foreach ($lines as $row) {
                $p = $products[$row['product_id']];
                // The shop-wide extra discount is spread proportionally so profit reports stay accurate.
                $share = $lineNet > 0 ? round($extraDiscount * $row['net'] / $lineNet, 2) : 0;
                $sale->items()->create([
                    'product_id' => $p->id, 'quantity' => $row['quantity'], 'unit_price' => $p->sale_price,
                    'unit_cost' => $p->cost_price, 'discount' => $row['line_discount'] + $share,
                    'line_total' => $row['gross'] - $row['line_discount'] - $share,
                ]);
                StockService::change($user->branch_id, $p->id, -$row['quantity'], 'sale', $sale->invoice_no, $user->id);
            }
            return $sale;
        });

        return response()->json(['ok' => true, 'url' => route('sales.show', ['sale' => $sale, 'print' => 1])]);
    }
}
