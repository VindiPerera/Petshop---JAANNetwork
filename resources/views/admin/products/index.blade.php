@extends('layouts.app')
@section('title', 'Products')
@section('actions')<a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Product</a>@endsection

@section('content')
<form class="mb-3 d-flex gap-2"><input name="q" value="{{ request('q') }}" class="form-control" style="max-width:320px" placeholder="Search name or SKU"><button class="btn btn-outline-secondary">Search</button></form>
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">Reorder</th><th>Status</th><th></th></tr></thead>
    @forelse($products as $p)
        <tr><td>{{ $p->sku }}</td><td>{{ $p->name }}</td><td>{{ $p->category?->name }}</td>
            <td class="text-end">{{ number_format($p->cost_price, 2) }}</td><td class="text-end">{{ number_format($p->sale_price, 2) }}</td><td class="text-end">{{ $p->reorder_level }}</td>
            <td>{!! $p->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' !!}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.products.edit', $p) }}">Edit</a></td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
