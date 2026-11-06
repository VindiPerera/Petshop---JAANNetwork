@extends('layouts.app')
@section('title', 'Sales')

@section('content')
@include('partials.filters', ['dates' => true, 'search' => true])
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Invoice</th><th>Date</th><th>Branch</th><th>Customer</th><th>Cashier</th><th>Payment</th><th class="text-end">Total</th><th></th></tr></thead>
    @forelse($sales as $s)
        <tr><td><a href="{{ route('sales.show', $s) }}">{{ $s->invoice_no }}</a></td><td>{{ $s->created_at->format('Y-m-d H:i') }}</td><td>{{ $s->branch->name }}</td>
            <td>{{ $s->customer?->name ?? 'Walk-in' }}</td><td>{{ $s->user->name }}</td><td class="text-capitalize">{{ $s->payment_method }}</td><td class="text-end">{{ number_format($s->total, 2) }}</td>
            <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="{{ route('sales.show', $s) }}"><i class="bi bi-eye"></i> View</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', [$s, 'print' => 1]) }}"><i class="bi bi-printer"></i> Reprint</a></td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No sales found.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
