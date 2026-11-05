@extends('layouts.app')
@section('title', $customer->name)
@section('actions')<a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary">Edit</a>@endsection

@section('content')
<div class="card shadow-sm mb-3"><div class="card-body row">
    <div class="col-md-3"><div class="text-muted small">Phone</div>{{ $customer->phone ?: '—' }}</div>
    <div class="col-md-3"><div class="text-muted small">Email</div>{{ $customer->email ?: '—' }}</div>
    <div class="col-md-3"><div class="text-muted small">Pet</div>{{ $customer->pet_name ?: '—' }} {{ $customer->pet_type }}</div>
    <div class="col-md-3"><div class="text-muted small">Address</div>{{ $customer->address ?: '—' }}</div>
</div></div>
<div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Purchase history</div><table class="table mb-0">
    <thead><tr><th>Invoice</th><th>Date</th><th>Branch</th><th class="text-end">Total</th></tr></thead>
    @forelse($sales as $s)
        <tr><td><a href="{{ route('sales.show', $s) }}">{{ $s->invoice_no }}</a></td><td>{{ $s->created_at->format('Y-m-d H:i') }}</td><td>{{ $s->branch->name }}</td><td class="text-end">{{ number_format($s->total, 2) }}</td></tr>
    @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No purchases yet.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
