@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'New Product')

@section('content')
<form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="card shadow-sm" style="max-width:760px">
    @csrf @if($product->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">SKU / Barcode</label><input name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required></div>
        <div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $product->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Category</label>
            <select name="category_id" class="form-select"><option value="">—</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
            </select></div>
        <div class="col-md-3"><label class="form-label">Unit</label><input name="unit" class="form-control" value="{{ old('unit', $product->unit) }}" required></div>
        <div class="col-md-3"><label class="form-label">Reorder level</label><input type="number" min="0" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product->reorder_level) }}" required></div>
        <div class="col-md-6"><label class="form-label">Cost price</label><input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" required></div>
        <div class="col-md-6"><label class="form-label">Sale price</label><input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}" required></div>
        @unless($product->exists)
            <div class="col-md-6"><label class="form-label">Opening warehouse quantity (optional)</label><input type="number" min="0" name="opening_qty" class="form-control" value="{{ old('opening_qty', 0) }}"><div class="form-text">For stock already in the warehouse. Use Receive Stock for new deliveries.</div></div>
        @endunless
        @if($product->exists)
            <div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="a" @checked(old('is_active', $product->is_active))><label class="form-check-label" for="a">Active (available for sale/transfer)</label></div>
        @endif
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.products.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
