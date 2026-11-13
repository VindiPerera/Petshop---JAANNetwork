<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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
    /**
     * Location this bill is for: a shop user always sells from their own shop; the main admin picks any
     * active location (the main warehouse is the default).
     */
    private function location(Request $request): Branch
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return $user->branch;
        }
        $query = Branch::where('is_active', true);
        return ($request->filled('branch_id') ? (clone $query)->find($request->input('branch_id')) : null)
            ?? $query->orderBy('type')->orderBy('id')->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $branch = $this->location($request);
        $branchId = $branch->id;

        // Only products this branch actually holds (received through a GRN) and that are in stock.
        $stock = Stock::where('branch_id', $branchId)->where('quantity', '>', 0)->pluck('quantity', 'product_id');

        $products = Product::with('category')->where('is_active', true)->whereIn('id', $stock->keys())->orderBy('name')->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'sku' => $p->sku, 'barcode' => (string) $p->barcode, 'name' => $p->name, 'category' => $p->category?->name,
                'price' => (float) $p->sale_price, 'stock' => (int) $stock[$p->id],
            ])->values();

        return view('pos', [
            'products' => $products,
            'branchInfo' => $branch->only('name', 'address', 'phone', 'email', 'tax_no', 'currency', 'receipt_footer') + ['logo' => $branch->logo_url],
            'cashier' => $user->name,
            'branchId' => $branchId,
            'locations' => $user->isAdmin() ? Branch::where('is_active', true)->orderBy('type')->orderBy('name')->get() : collect(),
            // Admin-created customers have no branch, so the admin's list also includes those.
            'customers' => Customer::where(fn ($q) => $q->where('branch_id', $branchId)->when($user->isAdmin(), fn ($w) => $w->orWhereNull('branch_id')))->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => $request->user()->isAdmin() ? 'required|exists:branches,id' : 'nullable',
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:amount,percent',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:amount,percent',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);
        $user = $request->user();
        $branch = $this->location($request);
        abort_unless($user->isAdmin() || $branch->id === $user->branch_id, 403);

        $sale = DB::transaction(function () use ($data, $user, $branch) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

            // Per-line totals, each line discount capped at the line value.
            $lines = collect($data['items'])->map(function ($i) use ($products) {
                $gross = $i['quantity'] * $products[$i['product_id']]->sale_price;
                $disc = $this->discountAmount($i['discount'] ?? 0, $i['discount_type'] ?? 'amount', $gross);
                return $i + ['gross' => $gross, 'line_discount' => $disc, 'net' => $gross - $disc];
            });

            $lineNet = round($lines->sum('net'), 2);
            $extraDiscount = $this->discountAmount($data['discount'] ?? 0, $data['discount_type'] ?? 'amount', $lineNet);
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
                'branch_id' => $branch->id, 'customer_id' => $data['customer_id'] ?? null, 'user_id' => $user->id,
                'subtotal' => round($lines->sum('gross'), 2), 'discount' => $totalDiscount, 'total' => $total,
                'paid' => $tendered, 'payment_method' => $methods->count() > 1 ? 'split' : $methods->first(),
            ]);
            $sale->update(['invoice_no' => 'INV-'.$branch->code.'-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT)]);

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
                StockService::change($branch->id, $p->id, -$row['quantity'], 'sale', $sale->invoice_no, $user->id);
            }
            return $sale;
        });

        return response()->json(['ok' => true, 'url' => route('sales.show', ['sale' => $sale, 'print' => 1])]);
    }
}
