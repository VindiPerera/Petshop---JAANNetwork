<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Online / phone orders. Created by the main admin (any location) or a branch (own shop),
 * then billed: billing turns the order into a normal Sale (channel = online) so it shows up in every report.
 */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $orders = Order::with('branch', 'user')->withCount('items')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('order_no', 'like', "%$s%")->orWhere('customer_name', 'like', "%$s%")->orWhere('customer_phone', 'like', "%$s%")))
            ->latest()->paginate(25)->withQueryString();

        return view('orders.index', ['orders' => $orders, 'branches' => $this->branchOptions()]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $branches = $user->isAdmin() ? Branch::where('is_active', true)->orderBy('type')->orderBy('name')->get() : collect([$user->branch]);

        // stock[branch_id][product_id] = qty, so the editor can show availability for the chosen location.
        $stock = Stock::whereIn('branch_id', $branches->pluck('id'))->get()->groupBy('branch_id')
            ->map(fn ($rows) => $rows->pluck('quantity', 'product_id'));

        $customers = Customer::when(! $user->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')->get(['id', 'name', 'phone', 'address']);

        return view('orders.create', [
            'branches' => $branches, 'stock' => $stock, 'customers' => $customers,
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'sale_price']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'branch_id' => $user->isAdmin() ? 'required|exists:branches,id' : 'nullable',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'delivery_address' => 'nullable|string|max:255',
            'source' => ['required', Rule::in(array_keys(Order::SOURCES))],
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:amount,percent',
            'delivery_fee' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:amount,percent',
        ]);
        $branchId = $user->isAdmin() ? (int) $data['branch_id'] : $user->branch_id;

        $order = DB::transaction(function () use ($data, $user, $branchId) {
            // Product-wise discount first (amount or %), then an optional whole-order discount on what is left.
            $lines = collect($data['items'])->map(function ($i) {
                $gross = round($i['quantity'] * $i['unit_price'], 2);
                $disc = $this->discountAmount($i['discount'] ?? 0, $i['discount_type'] ?? 'amount', $gross);
                return $i + ['gross' => $gross, 'line_discount' => $disc, 'line_total' => round($gross - $disc, 2)];
            });
            $subtotal = round($lines->sum('gross'), 2);
            $lineNet = round($lines->sum('line_total'), 2);
            $discount = $this->discountAmount($data['discount'] ?? 0, $data['discount_type'] ?? 'amount', $lineNet);
            $fee = round((float) ($data['delivery_fee'] ?? 0), 2);

            $order = Order::create([
                'order_no' => (string) Str::uuid(), 'branch_id' => $branchId, 'customer_id' => $data['customer_id'] ?? null, 'user_id' => $user->id,
                'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null, 'source' => $data['source'], 'status' => 'pending',
                'subtotal' => $subtotal, 'discount' => $discount, 'delivery_fee' => $fee, 'total' => round($lineNet - $discount + $fee, 2),
                'note' => $data['note'] ?? null,
            ]);
            $order->update(['order_no' => 'ORD-'.str_pad($order->id, 6, '0', STR_PAD_LEFT)]);
            $order->items()->createMany($lines->map(fn ($l) => [
                'product_id' => $l['product_id'], 'quantity' => $l['quantity'], 'unit_price' => $l['unit_price'], 'discount' => $l['line_discount'], 'line_total' => $l['line_total'],
            ])->all());
            return $order;
        });

        return redirect()->route('orders.show', $order)->with('ok', "Order {$order->order_no} created.");
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->load('branch', 'customer', 'user', 'items.product', 'sale');
        $stock = Stock::where('branch_id', $order->branch_id)->pluck('quantity', 'product_id');
        return view('orders.show', compact('order', 'stock'));
    }

    /** Move an open order along: pending -> confirmed -> shipped, or cancel it. */
    public function status(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $status = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'shipped', 'cancelled'])]])['status'];
        abort_unless($order->isOpen(), 422, 'This order is already closed.');
        $order->update(['status' => $status]);
        return back()->with('ok', "Order marked as {$status}.");
    }

    /** Create the bill: converts the order into a Sale, takes the stock and records the payments. */
    public function bill(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $data = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);
        $user = $request->user();

        $sale = DB::transaction(function () use ($data, $user, $order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail(); // stops two people billing the same order
            if (! $order->isOpen()) {
                throw ValidationException::withMessages(['order' => 'This order is already '.$order->status.'.']);
            }
            $order->load('items.product', 'branch');
            $total = (float) $order->total;

            $payments = collect($data['payments']);
            $tendered = round($payments->sum('amount'), 2);
            $cash = round($payments->where('method', 'cash')->sum('amount'), 2);
            if ($tendered < $total) {
                throw ValidationException::withMessages(['payments' => 'Amount paid is less than the order total.']);
            }
            if (round($tendered - $cash, 2) > $total) {
                throw ValidationException::withMessages(['payments' => 'Card/transfer amounts cannot exceed the total.']);
            }
            $change = round($tendered - $total, 2);
            if ($change > $cash) {
                throw ValidationException::withMessages(['payments' => 'Change can only be given from the cash amount.']);
            }

            $methods = $payments->pluck('method')->unique();
            $sale = Sale::create([
                'invoice_no' => (string) Str::uuid(), 'branch_id' => $order->branch_id, 'customer_id' => $order->customer_id, 'user_id' => $user->id,
                'subtotal' => $order->subtotal, 'discount' => round($order->items->sum('discount') + $order->discount, 2), 'delivery_fee' => $order->delivery_fee, 'total' => $total,
                'paid' => $tendered, 'payment_method' => $methods->count() > 1 ? 'split' : $methods->first(),
                'channel' => 'online', 'order_id' => $order->id,
            ]);
            $sale->update(['invoice_no' => 'INV-'.$order->branch->code.'-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT)]);

            // Payments are stored net of change so payment-method reports add up to the sale total.
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

            // The order-wide discount is spread over the lines by their net value (last line takes the rounding remainder)
            // and added to each line's own discount, so profit stays accurate.
            $lineNet = (float) $order->items->sum('line_total');
            $left = (float) $order->discount;
            $last = $order->items->count() - 1;
            foreach ($order->items as $idx => $item) {
                $share = $idx === $last ? $left : ($lineNet > 0 ? round($order->discount * $item->line_total / $lineNet, 2) : 0);
                $left = round($left - $share, 2);
                $sale->items()->create([
                    'product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price,
                    'unit_cost' => $item->product->cost_price, 'discount' => round($item->discount + $share, 2), 'line_total' => round($item->line_total - $share, 2),
                ]);
                StockService::change($order->branch_id, $item->product_id, -$item->quantity, 'sale', $sale->invoice_no, $user->id);
            }

            $order->update(['status' => 'completed', 'sale_id' => $sale->id]);
            return $sale;
        });

        return redirect()->route('sales.show', ['sale' => $sale, 'print' => 1]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($request->user()->isAdmin() || $order->branch_id === $request->user()->branch_id, 403);
    }
}
