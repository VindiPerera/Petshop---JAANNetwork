@extends('layouts.app')
@section('title', 'Invoice '.$sale->invoice_no)
@section('actions')
    @if(auth()->user()->role === 'branch')<a href="{{ route('pos') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> New sale</a>@endif
    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>
@endsection

@section('content')
<div class="card shadow-sm" style="max-width:640px">
    <div class="card-body">
        <div class="text-center mb-3"><h5 class="mb-0">{{ config('app.name') }}</h5><div>{{ $sale->branch->name }}</div><div class="small text-muted">{{ $sale->branch->address }} {{ $sale->branch->phone }}</div></div>
        <div class="d-flex justify-content-between small mb-2">
            <div>Invoice: <strong>{{ $sale->invoice_no }}</strong><br>Customer: {{ $sale->customer?->name ?? 'Walk-in' }}</div>
            <div class="text-end">{{ $sale->created_at->format('Y-m-d H:i') }}<br>Cashier: {{ $sale->user->name }}</div>
        </div>
        <table class="table table-sm">
            <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
            @foreach($sale->items as $i)
                <tr><td>{{ $i->product->name }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ number_format($i->unit_price, 2) }}</td><td class="text-end">{{ number_format($i->line_total, 2) }}</td></tr>
            @endforeach
        </table>
        <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ number_format($sale->subtotal, 2) }}</span></div>
        <div class="d-flex justify-content-between"><span>Discount</span><span>{{ number_format($sale->discount, 2) }}</span></div>
        <div class="d-flex justify-content-between fw-bold fs-5 border-top mt-1 pt-1"><span>Total</span><span>{{ number_format($sale->total, 2) }}</span></div>
        <div class="d-flex justify-content-between"><span>Paid ({{ $sale->payment_method }})</span><span>{{ number_format($sale->paid, 2) }}</span></div>
        <div class="d-flex justify-content-between"><span>Change</span><span>{{ number_format($sale->paid - $sale->total, 2) }}</span></div>
        <p class="text-center text-muted small mt-3 mb-0">Thank you for shopping with us!</p>
    </div>
</div>
@endsection
