@extends('layouts.app')
@section('title', 'Online Orders')
@section('actions')<a href="{{ route('orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Order</a>@endsection

@section('content')
@include('partials.filters', ['dates' => true, 'search' => true])
<div class="d-flex flex-wrap gap-1 mb-3 no-print">
    <a class="btn btn-sm {{ request('status') ? 'btn-light border' : 'btn-primary' }}" href="{{ route('orders.index', request()->except('status', 'page')) }}">All</a>
    @foreach(\App\Models\Order::STATUSES as $key => $label)
        <a class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-light border' }}" href="{{ route('orders.index', array_merge(request()->except('page'), ['status' => $key])) }}">{{ $label }}</a>
    @endforeach
</div>
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Source</th>@if(auth()->user()->isAdmin())<th>Fulfilled from</th>@endif<th class="text-end">Items</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
    @forelse($orders as $o)
        <tr>
            <td><a href="{{ route('orders.show', $o) }}">{{ $o->order_no }}</a></td>
            <td>{{ $o->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ $o->customer_name }}@if($o->customer_phone)<div class="small text-muted">{{ $o->customer_phone }}</div>@endif</td>
            <td>{{ \App\Models\Order::SOURCES[$o->source] ?? $o->source }}</td>
            @if(auth()->user()->isAdmin())<td>{{ $o->branch->name }}</td>@endif
            <td class="text-end">{{ $o->items_count }}</td>
            <td class="text-end">{{ number_format($o->total, 2) }}</td>
            <td><span class="badge text-bg-{{ \App\Models\Order::STATUS_COLORS[$o->status] ?? 'secondary' }}">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.show', $o) }}"><i class="bi bi-eye"></i> View</a></td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
