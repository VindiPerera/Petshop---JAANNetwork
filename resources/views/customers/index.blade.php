@extends('layouts.app')
@section('title', 'Customers')
@section('actions')<a href="{{ route('customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Customer</a>@endsection

@section('content')
@include('partials.filters', ['search' => true])
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Phone</th><th>Pet</th>@if(auth()->user()->isAdmin())<th>Branch</th>@endif<th class="text-end">Bills</th><th class="text-end">Total spent</th><th></th></tr></thead>
    @forelse($customers as $c)
        <tr><td><a href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></td><td>{{ $c->phone }}</td><td>{{ trim($c->pet_name.' '.($c->pet_type ? '('.$c->pet_type.')' : '')) }}</td>
            @if(auth()->user()->isAdmin())<td>{{ $c->branch?->name ?? '—' }}</td>@endif
            <td class="text-end">{{ $c->sales_count }}</td><td class="text-end">{{ number_format($c->sales_sum_total ?? 0, 2) }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.edit', $c) }}">Edit</a></td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $customers->links() }}</div>
@endsection
