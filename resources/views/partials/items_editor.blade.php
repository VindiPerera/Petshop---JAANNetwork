{{-- Multi-row product editor. $products, $withCost (bool), $showStock (bool) --}}
<table class="table align-middle" id="items-table">
    <thead><tr><th>Product</th>@if($showStock)<th class="text-end" style="width:110px">In warehouse</th>@endif<th style="width:130px">Quantity</th>@if($withCost)<th style="width:150px">Unit cost</th>@endif<th style="width:50px"></th></tr></thead>
    <tbody></tbody>
</table>
<button type="button" class="btn btn-outline-primary btn-sm" id="add-row"><i class="bi bi-plus-lg"></i> Add product</button>

<template id="row-tpl">
    <tr>
        <td><select class="form-select prod" required>
            <option value="">Select product…</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->wh_stock ?? 0 }}">{{ $p->sku }} — {{ $p->name }}</option>
            @endforeach
        </select></td>
        @if($showStock)<td class="text-end stock-cell">—</td>@endif
        <td><input type="number" min="1" class="form-control qty" required></td>
        @if($withCost)<td><input type="number" min="0" step="0.01" class="form-control cost" required></td>@endif
        <td><button type="button" class="btn btn-sm btn-outline-danger rm">&times;</button></td>
    </tr>
</template>

@push('scripts')
<script>
(() => {
    const body = document.querySelector('#items-table tbody');
    const tpl = document.getElementById('row-tpl');
    let i = 0;
    function addRow() {
        const tr = tpl.content.firstElementChild.cloneNode(true);
        const n = i++;
        const sel = tr.querySelector('.prod'), qty = tr.querySelector('.qty'), cost = tr.querySelector('.cost'), cell = tr.querySelector('.stock-cell');
        sel.name = `items[${n}][product_id]`; qty.name = `items[${n}][quantity]`; if (cost) cost.name = `items[${n}][unit_cost]`;
        sel.addEventListener('change', () => {
            const o = sel.selectedOptions[0];
            if (cost && o.value) cost.value = o.dataset.cost;
            if (cell) { cell.textContent = o.value ? o.dataset.stock : '—'; qty.max = o.value ? o.dataset.stock : ''; }
        });
        tr.querySelector('.rm').addEventListener('click', () => { if (body.children.length > 1) tr.remove(); });
        body.appendChild(tr);
    }
    document.getElementById('add-row').addEventListener('click', addRow);
    addRow();
})();
</script>
@endpush
