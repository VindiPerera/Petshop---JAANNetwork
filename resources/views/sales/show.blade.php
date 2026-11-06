@extends('layouts.app')
@section('title', 'Invoice '.$sale->invoice_no)
@section('actions')
    @if(auth()->user()->role === 'branch')<a href="{{ route('pos') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> New sale</a>@endif
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print / Reprint</button>
@endsection

@push('head')
<style>
    .receipt { width: 80mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 14px 12px; font-family: 'Courier New', monospace; font-size: 12px; color: #000; border: 1px dashed #cbd5e1; border-radius: 8px; }
    .receipt .c { text-align: center; }
    .receipt .logo { max-width: 120px; max-height: 70px; margin-bottom: 4px; }
    .receipt .shop { font-size: 15px; font-weight: 700; }
    .receipt hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; opacity: 1; }
    .receipt table { width: 100%; border-collapse: collapse; }
    .receipt th { text-align: left; border-bottom: 1px dashed #000; padding: 2px 0; font-weight: 700; }
    .receipt td { padding: 2px 0; vertical-align: top; }
    .receipt .r { text-align: right; }
    .receipt .row2 { display: flex; justify-content: space-between; }
    .receipt .big { font-size: 14px; font-weight: 700; }
    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        main { padding: 0 !important; }
        .receipt { width: 100%; border: 0; padding: 0; border-radius: 0; }
    }
</style>
@endpush

@section('content')
@php $b = $sale->branch; $cur = $b->currency ?: 'Rs.'; $m = fn ($n) => $cur.' '.number_format($n, 2); @endphp
<div class="receipt" id="receipt">
    <div class="c">
        @if($b->logo_url)<img src="{{ $b->logo_url }}" class="logo" alt="logo"><br>@endif
        <div class="shop">{{ $b->name }}</div>
        @if($b->address)<div>{{ $b->address }}</div>@endif
        @if($b->phone)<div>Tel: {{ $b->phone }}</div>@endif
        @if($b->email)<div>{{ $b->email }}</div>@endif
        @if($b->tax_no)<div>Tax/VAT No: {{ $b->tax_no }}</div>@endif
    </div>
    <hr>
    <div class="row2"><span>Invoice</span><strong>{{ $sale->invoice_no }}</strong></div>
    <div class="row2"><span>Date</span><span>{{ $sale->created_at->format('Y-m-d H:i') }}</span></div>
    <div class="row2"><span>Cashier</span><span>{{ $sale->user->name }}</span></div>
    <div class="row2"><span>Customer</span><span>{{ $sale->customer?->name ?? 'Walk-in' }}</span></div>
    <hr>
    <table>
        <thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th></tr></thead>
        @foreach($sale->items as $i)
            <tr><td>{{ $i->product->name }}</td><td class="r">{{ $i->quantity }}</td><td class="r">{{ number_format($i->unit_price, 2) }}</td><td class="r">{{ number_format($i->quantity * $i->unit_price, 2) }}</td></tr>
            @if($i->discount > 0)<tr><td colspan="3" class="r">&nbsp;&nbsp;Discount</td><td class="r">-{{ number_format($i->discount, 2) }}</td></tr>@endif
        @endforeach
    </table>
    <hr>
    <div class="row2"><span>Subtotal</span><span>{{ $m($sale->subtotal) }}</span></div>
    @if($sale->discount > 0)<div class="row2"><span>Total discount</span><span>-{{ $m($sale->discount) }}</span></div>@endif
    <div class="row2 big"><span>TOTAL</span><span>{{ $m($sale->total) }}</span></div>
    <hr>
    @foreach($sale->payments as $p)
        <div class="row2"><span>Paid ({{ ucfirst($p->method) }})</span><span>{{ $m($p->amount) }}</span></div>
    @endforeach
    @if($sale->paid - $sale->total > 0)
        <div class="row2"><span>Cash tendered</span><span>{{ $m($sale->paid - $sale->payments->where('method', '!=', 'cash')->sum('amount')) }}</span></div>
        <div class="row2"><span>Change</span><span>{{ $m($sale->paid - $sale->total) }}</span></div>
    @endif
    <hr>
    <div class="c">{{ $b->receipt_footer ?: 'Thank you for shopping with us!' }}</div>
</div>
@endsection

@push('scripts')
@if(request()->boolean('print'))
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
@endif
@endpush
