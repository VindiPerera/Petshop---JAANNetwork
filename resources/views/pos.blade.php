@extends('layouts.app')
@section('title', 'POS Billing')

@section('content')
<div class="row g-3" id="pos">
    <div class="col-lg-6 col-xl-7">
        <div class="card">
            <div class="card-body">
                <input id="search" class="form-control form-control-lg mb-3" placeholder="Search name / scan barcode / SKU and press Enter" autofocus autocomplete="off">
                <div id="grid" class="row g-2" style="max-height:72vh;overflow:auto"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-xl-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="customer" class="form-select"><option value="">Walk-in customer</option>
                        @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone }}</option>@endforeach</select>
                    <button class="btn btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#custModal" title="New customer"><i class="bi bi-person-plus"></i></button>
                </div>

                <table class="table table-sm align-middle mb-2">
                    <thead><tr><th>Item</th><th style="width:78px">Qty</th><th style="width:90px">Disc.</th><th class="text-end">Total</th><th></th></tr></thead>
                    <tbody id="cart"></tbody>
                </table>

                <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="subtotal">0.00</strong></div>
                <div class="d-flex justify-content-between"><span>Item discounts</span><strong id="itemdisc" class="text-danger">0.00</strong></div>
                <div class="d-flex justify-content-between align-items-center my-1"><span>Extra discount (whole bill)</span><input id="discount" type="number" min="0" step="0.01" value="0" class="form-control form-control-sm text-end" style="width:110px"></div>
                <div class="d-flex justify-content-between fs-4 fw-bold border-top pt-2"><span>Total</span><span id="total">0.00</span></div>

                <div class="mt-2 border-top pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold">Payment</span>
                        <div>
                            <button type="button" id="fill" class="btn btn-sm btn-outline-secondary">Exact</button>
                            <button type="button" id="split" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Split</button>
                        </div>
                    </div>
                    <div id="pays"></div>
                    <div class="d-flex justify-content-between mt-2"><span id="balLabel">Balance due</span><strong id="balance">0.00</strong></div>
                </div>

                <div id="err" class="text-danger small mt-2"></div>
                <div class="d-flex gap-2 mt-2">
                    <button id="preview" class="btn btn-outline-secondary flex-fill" disabled><i class="bi bi-eye"></i> Preview bill</button>
                    <button id="pay" class="btn btn-success flex-fill" disabled><i class="bi bi-printer"></i> Complete &amp; print</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bill preview --}}
<div class="modal fade" id="prevModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Bill preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="prevBody" style="font-family:'Courier New',monospace;font-size:12px"></div>
    <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button><button class="btn btn-success" id="payModal"><i class="bi bi-printer"></i> Complete &amp; print</button></div>
</div></div></div>

<div class="modal fade" id="custModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" id="custForm">
    <div class="modal-header"><h5 class="modal-title">Quick add customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
        <div class="col-12"><input name="name" class="form-control" placeholder="Name *" required></div>
        <div class="col-6"><input name="phone" class="form-control" placeholder="Phone"></div>
        <div class="col-6"><input name="pet_name" class="form-control" placeholder="Pet name"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endsection

@push('scripts')
<script>
const PRODUCTS = @json($products);
const BRANCH = @json($branchInfo);
const CASHIER = @json($cashier);
const csrf = document.querySelector('meta[name=csrf-token]').content;
const $ = (id) => document.getElementById(id);
const CUR = BRANCH.currency || 'Rs.';
const num = (n) => Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const byId = Object.fromEntries(PRODUCTS.map(p => [p.id, p]));
const cart = {};                         // id -> {qty, disc}
let pays = [{method: 'cash', amount: ''}]; // payment lines
let busy = false;

/* ---------- product grid ---------- */
function renderGrid() {
    const q = $('search').value.trim().toLowerCase();
    const list = PRODUCTS.filter(p => !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || (p.barcode || '').includes(q));
    $('grid').innerHTML = list.map(p => `
        <div class="col-6 col-md-4"><button type="button" class="btn w-100 text-start border" data-id="${p.id}">
            <div class="fw-semibold small">${esc(p.name)}</div>
            <div class="d-flex justify-content-between small"><span>${num(p.price)}</span><span class="${p.stock <= 5 ? 'text-danger' : 'text-muted'}">${p.stock} left</span></div>
        </button></div>`).join('')
        || `<div class="text-muted p-3">${PRODUCTS.length ? 'No matching products.' : 'No stock in this shop yet. Ask the main admin to send a GRN.'}</div>`;
}

function add(id) {
    const p = byId[id]; if (!p) return;
    const line = cart[id] || (cart[id] = {qty: 0, disc: 0});
    if (line.qty + 1 > p.stock) { $('err').textContent = `Only ${p.stock} of "${p.name}" in stock.`; if (!line.qty) delete cart[id]; return; }
    $('err').textContent = ''; line.qty++; renderCart();
}

/* ---------- cart ---------- */
const lineGross = (id) => byId[id].price * cart[id].qty;
const lineDisc = (id) => Math.min(Math.max(+cart[id].disc || 0, 0), lineGross(id));

function renderCart() {
    const ids = Object.keys(cart);
    $('cart').innerHTML = ids.map(id => { const p = byId[id];
        return `<tr><td class="small">${esc(p.name)}<div class="text-muted">${num(p.price)}</div></td>
        <td><input type="number" min="1" max="${p.stock}" value="${cart[id].qty}" data-qty="${id}" class="form-control form-control-sm px-1"></td>
        <td><input type="number" min="0" step="0.01" value="${cart[id].disc || ''}" placeholder="0" data-disc="${id}" class="form-control form-control-sm px-1 text-end"></td>
        <td class="text-end">${num(lineGross(id) - lineDisc(id))}</td>
        <td><button type="button" class="btn btn-sm text-danger" data-rm="${id}">&times;</button></td></tr>`; }).join('')
        || '<tr><td colspan="5" class="text-center text-muted py-3">Cart is empty</td></tr>';
    totals();
}

function calc() {
    const ids = Object.keys(cart);
    const gross = ids.reduce((s, id) => s + lineGross(id), 0);
    const itemDisc = ids.reduce((s, id) => s + lineDisc(id), 0);
    const net = gross - itemDisc;
    const extra = Math.min(Math.max(parseFloat($('discount').value) || 0, 0), net);
    return {gross, itemDisc, extra, total: Math.round((net - extra) * 100) / 100};
}

/* ---------- payments ---------- */
function renderPays() {
    $('pays').innerHTML = pays.map((p, i) => `
        <div class="input-group input-group-sm mb-1">
            <select class="form-select" data-pm="${i}" style="max-width:130px">
                ${['cash','card','transfer'].map(m => `<option value="${m}" ${p.method === m ? 'selected' : ''}>${m[0].toUpperCase() + m.slice(1)}</option>`).join('')}
            </select>
            <input type="number" min="0" step="0.01" class="form-control text-end" data-pa="${i}" value="${p.amount}" placeholder="Amount">
            ${pays.length > 1 ? `<button type="button" class="btn btn-outline-danger" data-prm="${i}">&times;</button>` : ''}
        </div>`).join('');
}

function paidSum() { return pays.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0); }

function totals() {
    const c = calc(), paid = paidSum();
    $('subtotal').textContent = num(c.gross); $('itemdisc').textContent = '-' + num(c.itemDisc); $('total').textContent = num(c.total);
    const diff = Math.round((paid - c.total) * 100) / 100;
    $('balLabel').textContent = diff >= 0 ? 'Change' : 'Balance due';
    $('balance').textContent = num(Math.abs(diff));
    $('balance').className = diff >= 0 ? 'text-success' : 'text-danger';
    const ok = c.total > 0 && diff >= 0 && !busy;
    $('pay').disabled = !ok; $('payModal').disabled = !ok; $('preview').disabled = !(c.total > 0);
    return c;
}

/* ---------- bill preview ---------- */
function previewHtml() {
    const c = calc(), ids = Object.keys(cart), paid = paidSum();
    const m = (n) => `${esc(CUR)} ${num(n)}`;
    const cash = pays.filter(p => p.method === 'cash').reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
    const row = (a, b, cls = '') => `<div class="d-flex justify-content-between ${cls}"><span>${a}</span><span>${b}</span></div>`;
    return `<div style="font-family:Inter,system-ui,sans-serif;font-size:12px;line-height:1.5" class="text-center">${BRANCH.logo ? `<img src="${esc(BRANCH.logo)}" style="width:100%;max-height:90px;object-fit:contain"><br>` : ''}
        <div class="fw-bold" style="font-size:14px">${esc(BRANCH.name)}</div>
        ${BRANCH.address ? `<div>${esc(BRANCH.address)}</div>` : ''}${BRANCH.phone ? `<div>Tel: ${esc(BRANCH.phone)}</div>` : ''}
        ${BRANCH.email ? `<div>${esc(BRANCH.email)}</div>` : ''}${BRANCH.tax_no ? `<div>Tax/VAT No: ${esc(BRANCH.tax_no)}</div>` : ''}</div><hr class="my-1">
        ${row('Date', new Date().toLocaleString())}${row('Cashier', esc(CASHIER))}
        ${row('Customer', esc($('customer').selectedOptions[0].text.trim() || 'Walk-in'))}<hr class="my-1">
        ${ids.map(id => { const p = byId[id], l = cart[id];
            return row(`${esc(p.name)}<br>&nbsp;&nbsp;${l.qty} x ${num(p.price)}`, num(lineGross(id)))
                 + (lineDisc(id) > 0 ? row('&nbsp;&nbsp;Discount', '-' + num(lineDisc(id))) : ''); }).join('')}<hr class="my-1">
        ${row('Subtotal', m(c.gross))}${c.itemDisc + c.extra > 0 ? row('Total discount', '-' + m(c.itemDisc + c.extra)) : ''}
        ${row('TOTAL', m(c.total), 'fw-bold fs-6')}<hr class="my-1">
        ${pays.filter(p => parseFloat(p.amount) > 0).map(p => row(`Paid (${p.method})`, m(parseFloat(p.amount)))).join('')}
        ${paid - c.total > 0 ? row('Change', m(paid - c.total)) : ''}<hr class="my-1">
        <div class="text-center">${esc(BRANCH.receipt_footer || 'Thank you for shopping with us!')}</div>`;
}

/* ---------- events ---------- */
$('grid').addEventListener('click', e => { const b = e.target.closest('[data-id]'); if (b) add(+b.dataset.id); });
$('cart').addEventListener('click', e => { const b = e.target.closest('[data-rm]'); if (b) { delete cart[b.dataset.rm]; renderCart(); } });
$('cart').addEventListener('change', e => {
    const q = e.target.dataset.qty, d = e.target.dataset.disc;
    if (q) cart[q].qty = Math.max(1, Math.min(parseInt(e.target.value) || 1, byId[q].stock));
    if (d) cart[d].disc = Math.max(parseFloat(e.target.value) || 0, 0);
    renderCart();
});
$('search').addEventListener('input', renderGrid);
$('search').addEventListener('keydown', e => { // Enter on an exact SKU / single match adds it (barcode scanners)
    if (e.key !== 'Enter') return; e.preventDefault();
    const q = e.target.value.trim().toLowerCase(); if (!q) return;
    const matches = PRODUCTS.filter(p => p.name.toLowerCase().includes(q));
    const hit = PRODUCTS.find(p => p.sku.toLowerCase() === q || p.barcode === q) || (matches.length === 1 ? matches[0] : null);
    if (hit) { add(hit.id); e.target.value = ''; renderGrid(); }
});
$('discount').addEventListener('input', totals);

$('pays').addEventListener('input', e => { const i = e.target.dataset.pa; if (i !== undefined) { pays[i].amount = e.target.value; totals(); } });
$('pays').addEventListener('change', e => { const i = e.target.dataset.pm; if (i !== undefined) pays[i].method = e.target.value; });
$('pays').addEventListener('click', e => { const b = e.target.closest('[data-prm]'); if (b) { pays.splice(+b.dataset.prm, 1); renderPays(); totals(); } });
$('split').addEventListener('click', () => {
    const rest = Math.max(calc().total - paidSum(), 0);
    pays.push({method: pays.some(p => p.method === 'cash') ? 'card' : 'cash', amount: rest ? rest.toFixed(2) : ''});
    renderPays(); totals();
});
$('fill').addEventListener('click', () => { // put the remaining amount on the last payment line
    const last = pays[pays.length - 1]; const others = paidSum() - (parseFloat(last.amount) || 0);
    last.amount = Math.max(calc().total - others, 0).toFixed(2); renderPays(); totals();
});

$('preview').addEventListener('click', () => { $('prevBody').innerHTML = previewHtml(); new bootstrap.Modal($('prevModal')).show(); });

async function complete() {
    if (busy) return; busy = true; totals(); $('err').textContent = '';
    bootstrap.Modal.getInstance($('prevModal'))?.hide();
    const res = await fetch("{{ route('pos.store') }}", {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({
            customer_id: $('customer').value || null, discount: calc().extra,
            items: Object.keys(cart).map(id => ({product_id: +id, quantity: cart[id].qty, discount: lineDisc(id)})),
            payments: pays.filter(p => parseFloat(p.amount) > 0).map(p => ({method: p.method, amount: parseFloat(p.amount)})),
        }),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.url) { window.location = data.url; return; }  // receipt page opens the print dialog
    $('err').textContent = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Sale failed.');
    busy = false; totals();
}
$('pay').addEventListener('click', complete);
$('payModal').addEventListener('click', complete);

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

renderGrid(); renderPays(); renderCart();
</script>
@endpush
