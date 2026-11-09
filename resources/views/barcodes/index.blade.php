@extends('layouts.app')
@section('title', 'Barcode Labels')

@section('content')
<form class="mb-3 d-flex gap-2"><input name="q" value="{{ request('q') }}" class="form-control" style="max-width:320px" placeholder="Search name, SKU or barcode"><button class="btn btn-outline-secondary">Search</button></form>
@error('copies')<div class="alert alert-danger">{{ $message }}</div>@enderror
<form method="post" action="{{ route('barcodes.print') }}" target="_blank">@csrf
    <div class="card shadow-sm mb-3"><div class="card-body row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Label size</label>
            <select name="size" class="form-select">
                <option value="small">Small (38 × 25 mm)</option>
                <option value="wide">2 inch (50 × 25 mm)</option>
                <option value="medium" selected>Medium (50 × 30 mm)</option>
                <option value="large">Large (70 × 40 mm)</option>
            </select></div>
        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="show_name" value="1" id="sn" checked><label class="form-check-label" for="sn">Show product name</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="show_price" value="1" id="sp" checked><label class="form-check-label" for="sp">Show price</label></div></div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('.cp').forEach(i => i.value = i.value > 0 ? i.value : 1)">1 label each</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('.cp').forEach(i => i.value = 0)">Clear</button>
            <button class="btn btn-primary"><i class="bi bi-printer"></i> Print labels</button>
        </div>
    </div></div>
    <div class="card shadow-sm"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Barcode</th><th>Product</th><th>SKU</th><th class="text-end">Price</th><th style="width:130px">Labels to print</th></tr></thead>
        @forelse($products as $p)
            <tr><td><code>{{ $p->barcode }}</code></td><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td class="text-end">{{ number_format($p->sale_price, 2) }}</td>
                <td><input type="number" min="0" max="500" class="form-control form-control-sm cp" name="copies[{{ $p->id }}]" value="{{ $preselect === $p->id ? 1 : 0 }}"></td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No products found.</td></tr>
        @endforelse
    </table></div>
</form>
@endsection
