@extends('layouts.app')
@section('title', 'New Online Order')

@section('content')
<form method="post" action="{{ route('orders.store') }}" class="card shadow-sm" id="order-form">@csrf
    <div class="card-body">
        <div class="row g-3 mb-3">
            @if(auth()->user()->isAdmin())
                <div class="col-md-3"><label class="form-label req">Fulfil from</label>
                    <select name="branch_id" id="branch" class="form-select" required>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }} ({{ $b->type }})</option>@endforeach
                    </select></div>
            @else
                <input type="hidden" id="branch" value="{{ $branches->first()->id }}">
            @endif
            <div class="col-md-3"><label class="form-label">Existing customer</label>
                <select name="customer_id" id="customer" class="form-select"><option value="">— new / walk-in —</option>
                    @foreach($customers as $c)<option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" data-address="{{ $c->address }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="form-label req">Customer name</label><input name="customer_name" id="c-name" class="form-control" value="{{ old('customer_name') }}" required></div>
            <div class="col-md-3"><label class="form-label">Phone</label><input name="customer_phone" id="c-phone" class="form-control" value="{{ old('customer_phone') }}"></div>
            <div class="col-md-6"><label class="form-label">Delivery address</label><input name="delivery_address" id="c-address" class="form-control" value="{{ old('delivery_address') }}"></div>
            <div class="col-md-3"><label class="form-label req">Order source</label>
                <select name="source" class="form-select" required>@foreach(\App\Models\Order::SOURCES as $k => $l)<option value="{{ $k }}" @selected(old('source', 'website') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Note</label><input name="note" class="form-control" value="{{ old('note') }}"></div>
        </div>

        <table class="table align-middle" id="items-table">
            <thead><tr><th>Product</th><th class="text-end" style="width:100px">Available</th><th style="width:120px">Quantity</th><th style="width:150px">Unit price</th><th class="text-end" style="width:130px">Line total</th><th style="width:50px"></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-outline-primary btn-sm" id="add-row"><i class="bi bi-plus-lg"></i> Add product</button>

        <div class="row justify-content-end mt-3"><div class="col-md-4">
            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="subtotal">0.00</strong></div>
            <div class="row g-2 mb-2 align-items-center"><div class="col-6">Discount</div><div class="col-6"><input type="number" min="0" step="0.01" name="discount" id="discount" class="form-control form-control-sm" value="{{ old('discount', 0) }}"></div></div>
            <div class="row g-2 mb-2 align-items-center"><div class="col-6">Delivery fee</div><div class="col-6"><input type="number" min="0" step="0.01" name="delivery_fee" id="fee" class="form-control form-control-sm" value="{{ old('delivery_fee', 0) }}"></div></div>
            <div class="d-flex justify-content-between border-top pt-2 fs-5"><span>Total</span><strong id="total">0.00</strong></div>
        </div></div>
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Create order</button> <a href="{{ route('orders.index') }}" class="btn btn-link">Cancel</a></div>
</form>

<template id="row-tpl">
    <tr>
        <td><select class="form-select prod" required>
            <option value="">Select product…</option>
            @foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->sale_price }}">{{ $p->sku }} — {{ $p->name }}</option>@endforeach
        </select></td>
        <td class="text-end avail">—</td>
        <td><input type="number" min="1" class="form-control qty" required></td>
        <td><input type="number" min="0" step="0.01" class="form-control price" required></td>
        <td class="text-end line">0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger rm">&times;</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
(() => {
    const stock = @json($stock);
    const body = document.querySelector('#items-table tbody');
    const tpl = document.getElementById('row-tpl');
    const branch = document.getElementById('branch');
    const num = v => parseFloat(v) || 0, fmt = n => n.toFixed(2);
    let i = 0;

    const avail = pid => (stock[branch.value] || {})[pid] ?? 0;

    function recalc() {
        let sub = 0;
        body.querySelectorAll('tr').forEach(tr => {
            const line = num(tr.querySelector('.qty').value) * num(tr.querySelector('.price').value);
            tr.querySelector('.line').textContent = fmt(line);
            sub += line;
            const sel = tr.querySelector('.prod');
            const a = sel.value ? avail(sel.value) : null;
            const cell = tr.querySelector('.avail');
            cell.textContent = a === null ? '—' : a;
            cell.classList.toggle('text-danger', a !== null && num(tr.querySelector('.qty').value) > a);
        });
        const disc = Math.min(num(document.getElementById('discount').value), sub);
        document.getElementById('subtotal').textContent = fmt(sub);
        document.getElementById('total').textContent = fmt(sub - disc + num(document.getElementById('fee').value));
    }

    function addRow() {
        const tr = tpl.content.firstElementChild.cloneNode(true);
        const n = i++;
        const sel = tr.querySelector('.prod'), qty = tr.querySelector('.qty'), price = tr.querySelector('.price');
        sel.name = `items[${n}][product_id]`; qty.name = `items[${n}][quantity]`; price.name = `items[${n}][unit_price]`;
        sel.addEventListener('change', () => { if (sel.value) { price.value = sel.selectedOptions[0].dataset.price; if (!qty.value) qty.value = 1; } recalc(); });
        tr.querySelector('.rm').addEventListener('click', () => { if (body.children.length > 1) { tr.remove(); recalc(); } });
        body.appendChild(tr);
    }

    document.getElementById('add-row').addEventListener('click', addRow);
    document.getElementById('order-form').addEventListener('input', recalc);
    branch.addEventListener('change', recalc);
    document.getElementById('customer').addEventListener('change', e => {
        const o = e.target.selectedOptions[0];
        if (!o.value) return;
        document.getElementById('c-name').value = o.dataset.name || '';
        document.getElementById('c-phone').value = o.dataset.phone || '';
        document.getElementById('c-address').value = o.dataset.address || '';
    });
    addRow();
})();
</script>
@endpush
