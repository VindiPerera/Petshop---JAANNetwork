@extends('layouts.app')
@section('title', 'Stock Movements')
@section('actions')<a href="{{ route('inventory.index', request()->only('branch_id')) }}" class="btn btn-outline-secondary">Back to inventory</a>@endsection

@section('content')
@include('partials.filters', ['dates' => true])
<div class="card shadow-sm"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Date</th><th>Location</th><th>Product</th><th>Type</th><th class="text-end">Qty</th><th>Reference</th><th>User</th></tr></thead>
    @forelse($movements as $m)
        <tr><td>{{ $m->created_at->format('Y-m-d H:i') }}</td><td>{{ $m->branch->name }}</td><td>{{ $m->product->name }}</td>
            <td><span class="badge text-bg-light border">{{ str_replace('_', ' ', $m->type) }}</span></td>
            <td class="text-end fw-bold {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
            <td>{{ $m->reference }}</td><td>{{ $m->user?->name }}</td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No movements.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $movements->links() }}</div>
@endsection
