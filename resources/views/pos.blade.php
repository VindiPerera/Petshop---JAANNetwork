@extends('layouts.app')
@section('title', 'POS Billing')

@section('content')
<div class="row g-3" id="pos">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <input id="search" class="form-control form-control-lg mb-3" placeholder="Search name / scan SKU and press Enter" autofocus autocomplete="off">
                <div id="grid" class="row g-2" style="max-height:68vh;overflow:auto"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="customer" class="form-select"><option value="">Walk-in customer</option>
                        @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone }}</option>@endforeach</select>
                    <button class="btn btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#custModal"><i class="bi bi-person-plus"></i></button>
                </div>
                <table class="table table-sm align-middle mb-2">
                    <thead><tr><th>Item</th><th style="width:105px">Qty</th><th class="text-end">Total</th><th></th></tr></thead>
                    <tbody id="cart"></tbody>
                </table>
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="subtotal">0.00</strong></div>
                <div class="d-flex justify-content-between align-items-center my-1"><span>Discount</span><input id="discount" type="number" min="0" step="0.01" value="0" class="form-control form-control-sm text-end" style="width:120px"></div>
                <div class="d-flex justify-content-between fs-4 fw-bold border-top pt-2"><span>Total</span><span id="total">0.00</span></div>
                <div class="row g-2 mt-1">
                    <div class="col-6"><select id="method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="transfer">Bank transfer</option></select></div>
                    <div class="col-6"><input id="paid" type="number" min="0" step="0.01" class="form-control text-end" placeholder="Amount paid"></div>
                </div>
                <div class="d-flex justify-content-between mt-2"><span>Change</span><strong id="change">0.00</strong></div>
                <div id="err" class="text-danger small mt-2"></div>
                <button id="pay" class="btn btn-success btn-lg w-100 mt-2" disabled><i class="bi bi-check2-circle"></i> Complete sale</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="custModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" id="custForm">
    <div class="modal-header"><h5 class="modal-title">Quick add customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
        <div class="col-12"><input name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-6"><input name="phone" class="form-control" placeholder="Phone"></div>
        <div class="col-6"><input name="pet_name" class="form-control" placeholder="Pet name"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endsection

@push('scripts')
<script>
const PRODUCTS = @json($products);
const csrf = document.querySelector('meta[name=csrf-token]').content;
const cart = {}; // id -> qty
const $ = (id) => document.getElementById(id);
const money = (n) => Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
const byId = Object.fromEntries(PRODUCTS.map(p => [p.id, p]));

function renderGrid() {
    const q = $('search').value.trim().toLowerCase();
    const list = PRODUCTS.filter(p => !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q));
    $('grid').innerHTML = list.map(p => `
        <div class="col-6 col-md-4"><button type="button" class="btn w-100 text-start border ${p.stock <= 0 ? 'bg-light text-muted' : 'btn-outline-dark'}" data-id="${p.id}" ${p.stock <= 0 ? 'disabled' : ''}>
            <div class="fw-semibold small">${p.name}</div>
            <div class="d-flex justify-content-between small"><span>${money(p.price)}</span><span class="${p.stock <= 5 ? 'text-danger' : 'text-muted'}">${p.stock} in stock</span></div>
        </button></div>`).join('') || '<div class="text-muted p-3">No products found.</div>';
}

function add(id) {
    const p = byId[id]; if (!p) return;
    if ((cart[id] || 0) + 1 > p.stock) { $('err').textContent = `Only ${p.stock} of "${p.name}" in stock.`; return; }
    $('err').textContent = ''; cart[id] = (cart[id] || 0) + 1; renderCart();
}

function renderCart() {
    const ids = Object.keys(cart);
    $('cart').innerHTML = ids.map(id => { const p = byId[id];
        return `<tr><td class="small">${p.name}<div class="text-muted">${money(p.price)}</div></td>
        <td><input type="number" min="1" max="${p.stock}" value="${cart[id]}" data-qty="${id}" class="form-control form-control-sm"></td>
        <td class="text-end">${money(p.price * cart[id])}</td>
        <td><button type="button" class="btn btn-sm text-danger" data-rm="${id}">&times;</button></td></tr>`; }).join('')
        || '<tr><td colspan="4" class="text-center text-muted py-3">Cart is empty</td></tr>';
    totals();
}

function totals() {
    const sub = Object.entries(cart).reduce((s, [id, q]) => s + byId[id].price * q, 0);
    const disc = Math.min(parseFloat($('discount').value) || 0, sub);
    const total = sub - disc, paid = parseFloat($('paid').value) || 0;
    $('subtotal').textContent = money(sub); $('total').textContent = money(total);
    $('change').textContent = money(Math.max(paid - total, 0));
    $('pay').disabled = !(total > 0 && paid >= total);
    return {sub, disc, total};
}

$('grid').addEventListener('click', e => { const b = e.target.closest('[data-id]'); if (b) add(+b.dataset.id); });
$('cart').addEventListener('click', e => { const b = e.target.closest('[data-rm]'); if (b) { delete cart[b.dataset.rm]; renderCart(); } });
$('cart').addEventListener('change', e => {
    const id = e.target.dataset.qty; if (!id) return;
    const q = Math.max(1, Math.min(parseInt(e.target.value) || 1, byId[id].stock)); cart[id] = q; renderCart();
});
$('search').addEventListener('input', renderGrid);
$('search').addEventListener('keydown', e => { // Enter on an exact SKU adds it (barcode scanners)
    if (e.key !== 'Enter') return; e.preventDefault();
    const q = e.target.value.trim().toLowerCase();
    const hit = PRODUCTS.find(p => p.sku.toLowerCase() === q) || (PRODUCTS.filter(p => p.name.toLowerCase().includes(q)).length === 1 ? PRODUCTS.find(p => p.name.toLowerCase().includes(q)) : null);
    if (hit) { add(hit.id); e.target.value = ''; renderGrid(); }
});
['discount', 'paid'].forEach(id => $(id).addEventListener('input', totals));

$('pay').addEventListener('click', async () => {
    const {total} = totals(); $('pay').disabled = true; $('err').textContent = '';
    const res = await fetch("{{ route('pos.store') }}", {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({
            customer_id: $('customer').value || null, discount: parseFloat($('discount').value) || 0,
            paid: parseFloat($('paid').value) || 0, payment_method: $('method').value,
            items: Object.entries(cart).map(([id, q]) => ({product_id: +id, quantity: q})),
        }),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.url) { window.location = data.url; return; }
    $('err').textContent = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Sale failed.');
    $('pay').disabled = false;
});

$('custForm').addEventListener('submit', async e => {
    e.preventDefault();
    const res = await fetch("{{ route('customers.store') }}", {
        method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: new FormData(e.target),
    });
    if (!res.ok) { alert('Could not save customer.'); return; }
    const c = await res.json();
    $('customer').add(new Option(`${c.name} ${c.phone ?? ''}`, c.id, true, true));
    bootstrap.Modal.getInstance($('custModal')).hide(); e.target.reset();
});

renderGrid(); renderCart();
</script>
@endpush
