@extends('layouts.app')
@section('title', 'Warehouse Receipts')
@section('actions')<a href="{{ route('admin.purchases.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Receive Stock</a>@endsection

@section('content')
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>No.</th><th>Date</th><th>Supplier</th><th>By</th><th class="text-end">Total cost</th></tr></thead>
    @forelse($purchases as $p)
        <tr><td><a href="{{ route('admin.purchases.show', $p) }}">{{ $p->purchase_no }}</a></td><td>{{ $p->received_date->format('Y-m-d') }}</td><td>{{ $p->supplier?->name ?? '—' }}</td><td>{{ $p->user->name }}</td><td class="text-end">{{ number_format($p->total_cost, 2) }}</td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">Nothing received yet.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $purchases->links() }}</div>
@endsection
