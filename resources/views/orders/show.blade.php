@extends('layouts.app')
@section('title', 'Order '.$order->order_no)
@section('actions')<a href="{{ route('orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> All orders</a>@endsection

@section('content')
@php $cur = $order->branch->currency ?: 'Rs.'; $m = fn ($n) => $cur.' '.number_format($n, 2); @endphp
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Items</span>
                <span class="badge text-bg-{{ \App\Models\Order::STATUS_COLORS[$order->status] ?? 'secondary' }}">{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</span>
            </div>
            <table class="table mb-0">
                <thead><tr><th>Product</th><th class="text-end">In stock</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Discount</th><th class="text-end">Total</th></tr></thead>
                @foreach($order->items as $i)
                    @php $have = (int) ($stock[$i->product_id] ?? 0); @endphp
                    <tr><td>{{ $i->product->sku }} — {{ $i->product->name }}</td>
                        <td class="text-end {{ $order->isOpen() && $have < $i->quantity ? 'text-danger fw-semibold' : '' }}">{{ $have }}</td>
                        <td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ number_format($i->unit_price, 2) }}</td><td class="text-end">{{ $i->discount > 0 ? '-'.number_format($i->discount, 2) : '—' }}</td><td class="text-end">{{ number_format($i->line_total, 2) }}</td></tr>
                @endforeach
                <tfoot>
                    <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end">{{ number_format($order->subtotal, 2) }}</td></tr>
                    @if($order->items->sum('discount') > 0)<tr><td colspan="5" class="text-end">Item discounts</td><td class="text-end">-{{ number_format($order->items->sum('discount'), 2) }}</td></tr>@endif
                    @if($order->discount > 0)<tr><td colspan="5" class="text-end">Order discount</td><td class="text-end">-{{ number_format($order->discount, 2) }}</td></tr>@endif
                    @if($order->delivery_fee > 0)<tr><td colspan="5" class="text-end">Delivery fee</td><td class="text-end">{{ number_format($order->delivery_fee, 2) }}</td></tr>@endif
                    <tr class="fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end">{{ number_format($order->total, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>

        @if($order->isOpen())
        <form method="post" action="{{ route('orders.bill', $order) }}" class="card shadow-sm" id="bill-form">@csrf
            <div class="card-header">Create bill</div>
            <div class="card-body">
                <p class="text-muted small mb-2">Billing takes the stock from <strong>{{ $order->branch->name }}</strong>, records the payment and prints the invoice. It is counted in the sales and profit reports.</p>
                <div id="pay-rows"></div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-pay"><i class="bi bi-plus-lg"></i> Add payment</button>
                <div class="d-flex justify-content-between mt-3"><span>Order total</span><strong>{{ $m($order->total) }}</strong></div>
                <div class="d-flex justify-content-between"><span>Paid</span><strong id="paid">0.00</strong></div>
                <div class="d-flex justify-content-between"><span>Change</span><strong id="change">0.00</strong></div>
            </div>
            <div class="card-footer bg-white"><button class="btn btn-success"><i class="bi bi-receipt"></i> Create bill &amp; print</button></div>
        </form>
        @elseif($order->sale)
            <div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center">
                <span>Billed as invoice <strong>{{ $order->sale->invoice_no }}</strong></span>
                <span><a class="btn btn-sm btn-outline-secondary" href="{{ route('sales.show', $order->sale) }}">View</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', [$order->sale, 'print' => 1]) }}"><i class="bi bi-printer"></i> Reprint</a></span>
            </div></div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3"><div class="card-header">Customer</div><div class="card-body">
            <div class="fw-semibold">{{ $order->customer_name }}</div>
            @if($order->customer_phone)<div><i class="bi bi-telephone text-muted"></i> {{ $order->customer_phone }}</div>@endif
            @if($order->delivery_address)<div><i class="bi bi-geo-alt text-muted"></i> {{ $order->delivery_address }}</div>@endif
            <hr>
            <div class="small text-muted">Source</div><div>{{ \App\Models\Order::SOURCES[$order->source] ?? $order->source }}</div>
            <div class="small text-muted mt-2">Fulfilled from</div><div>{{ $order->branch->name }}</div>
            <div class="small text-muted mt-2">Created</div><div>{{ $order->created_at->format('d M Y, H:i') }} by {{ $order->user->name }}</div>
            @if($order->note)<div class="small text-muted mt-2">Note</div><div>{{ $order->note }}</div>@endif
        </div></div>

        @if($order->isOpen())
        <div class="card shadow-sm"><div class="card-header">Update status</div><div class="card-body d-grid gap-2">
            @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'shipped' => 'Shipped'] as $k => $l)
                @if($order->status !== $k)
                    <form method="post" action="{{ route('orders.status', $order) }}">@csrf<input type="hidden" name="status" value="{{ $k }}"><button class="btn btn-outline-primary w-100">Mark as {{ $l }}</button></form>
                @endif
            @endforeach
            <form method="post" action="{{ route('orders.status', $order) }}" onsubmit="return confirm('Cancel this order?')">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-outline-danger w-100">Cancel order</button></form>
        </div></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($order->isOpen())
<script>
(() => {
    const total = {{ (float) $order->total }};
    const rows = document.getElementById('pay-rows');
    let n = 0;
    const num = v => parseFloat(v) || 0;
    function recalc() {
        const paid = [...rows.querySelectorAll('.amt')].reduce((s, e) => s + num(e.value), 0);
        document.getElementById('paid').textContent = paid.toFixed(2);
        document.getElementById('change').textContent = Math.max(0, paid - total).toFixed(2);
    }
    function add(amount) {
        const d = document.createElement('div');
        d.className = 'row g-2 mb-2';
        d.innerHTML = `<div class="col-5"><select class="form-select" name="payments[${n}][method]"><option value="cash">Cash</option><option value="card">Card</option><option value="transfer">Bank transfer</option></select></div>
            <div class="col-5"><input type="number" min="0.01" step="0.01" class="form-control amt" name="payments[${n}][amount]" value="${amount.toFixed(2)}" required></div>
            <div class="col-2"><button type="button" class="btn btn-outline-danger w-100 rm">&times;</button></div>`;
        d.querySelector('.rm').addEventListener('click', () => { if (rows.children.length > 1) { d.remove(); recalc(); } });
        d.querySelector('.amt').addEventListener('input', recalc);
        rows.appendChild(d); n++; recalc();
    }
    document.getElementById('add-pay').addEventListener('click', () => {
        const paid = [...rows.querySelectorAll('.amt')].reduce((s, e) => s + num(e.value), 0);
        add(Math.max(0, total - paid) || 0.01);
    });
    add(total);
})();
</script>
@endif
@endpush
