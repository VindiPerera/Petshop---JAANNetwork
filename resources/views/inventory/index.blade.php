@extends('layouts.app')
@section('title', 'Inventory')
@section('actions')
    <a class="btn btn-outline-danger" href="{{ url()->current().'?'.http_build_query(request()->except(['export', 'page']) + ['export' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <a class="btn btn-outline-success" href="{{ url()->current().'?'.http_build_query(request()->except(['export', 'page']) + ['export' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="{{ route('inventory.movements', request()->only('branch_id')) }}" class="btn btn-outline-secondary">Stock movements</a>
@endsection

@section('content')
<form class="card shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    @if(auth()->user()->isAdmin())
        <div class="col-md-3"><label class="form-label small mb-0">Location</label>
            <select name="branch_id" class="form-select form-select-sm"><option value="">All locations</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
    @endif
    <div class="col-md-3"><label class="form-label small mb-0">Category</label>
        <select name="category_id" class="form-select form-select-sm"><option value="">All</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small mb-0">Search</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm"></div>
    <div class="col-auto form-check ms-2 mb-1"><input type="checkbox" class="form-check-input" name="low" value="1" id="low" @checked(request()->boolean('low'))><label for="low" class="form-check-label small">Low stock only</label></div>
    <div class="col-auto form-check ms-2 mb-1"><input type="checkbox" class="form-check-input" name="in_stock" value="1" id="ins" @checked(request()->boolean('in_stock'))><label for="ins" class="form-check-label small">Hide out of stock</label></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button> <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Reset</a></div>
</div></form>

<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>SKU</th><th>Product</th><th>Category</th>@if(auth()->user()->isAdmin())<th>Location</th>@endif<th class="text-end">Qty</th><th class="text-end">Reorder</th><th class="text-end">Value (cost)</th></tr></thead>
    @forelse($stocks as $s)
        @php $low = $s->quantity <= $s->product->reorder_level; @endphp
        <tr class="{{ $low ? 'table-warning' : '' }}">
            <td>{{ $s->product->sku }}</td><td>{{ $s->product->name }}</td><td>{{ $s->product->category?->name }}</td>
            @if(auth()->user()->isAdmin())<td>{{ $s->branch->name }}</td>@endif
            <td class="text-end fw-bold {{ $low ? 'text-danger' : '' }}">{{ $s->quantity }}</td><td class="text-end">{{ $s->product->reorder_level }}</td>
            <td class="text-end">{{ number_format($s->quantity * $s->product->cost_price, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No stock records.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $stocks->links() }}</div>
@endsection
