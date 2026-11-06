@extends('layouts.app')
@section('title', 'Invoice '.$sale->invoice_no)
@section('actions')
    @if(auth()->user()->role === 'branch')<a href="{{ route('pos') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> New sale</a>@endif
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print / Reprint</button>
@endsection

@push('head')
<style>
    .receipt { width: 80mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 16px 14px 18px; font-family: 'Inter', system-ui, 'Segoe UI', Arial, sans-serif; font-size: 11.5px; line-height: 1.45; color: #111827; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 6px 24px rgba(15,23,42,.08); -webkit-font-smoothing: antialiased; }
    .receipt .c { text-align: center; }
    .receipt .logo { width: 100%; max-width: 100%; max-height: 90px; object-fit: contain; margin-bottom: 8px; }
    .receipt .shop { font-size: 16px; font-weight: 700; letter-spacing: -.01em; margin-bottom: 1px; }
    .receipt .muted { color: #6b7280; font-size: 10.5px; }
    .receipt hr { border: 0; border-top: 1px dashed #cbd5e1; margin: 9px 0; opacity: 1; }
    .receipt .title { text-align: center; font-size: 10px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: #374151; margin: 8px 0 2px; }
    .receipt .meta { display: grid; grid-template-columns: auto 1fr; column-gap: 10px; row-gap: 1px; }
    .receipt .meta span:nth-child(odd) { color: #6b7280; }
    .receipt .meta span:nth-child(even) { text-align: right; font-weight: 500; }
    .receipt table { width: 100%; border-collapse: collapse; }
    .receipt th { text-align: left; padding: 3px 0; font-size: 9.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #111827; }
    .receipt td { padding: 4px 0 1px; vertical-align: top; }
    .receipt td.sub { padding: 0 0 3px; color: #6b7280; font-size: 10.5px; }
    .receipt .r { text-align: right; font-variant-numeric: tabular-nums; }
    .receipt .row2 { display: flex; justify-content: space-between; gap: 8px; font-variant-numeric: tabular-nums; padding: 1px 0; }
    .receipt .total { display: flex; justify-content: space-between; align-items: baseline; margin: 6px 0 2px; padding: 7px 8px; background: #111827; color: #fff; border-radius: 6px; font-size: 14px; font-weight: 700; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .receipt .foot { text-align: center; margin-top: 4px; font-weight: 500; }
    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        main { padding: 0 !important; }
        .receipt { width: 100%; border: 0; padding: 0; border-radius: 0; box-shadow: none; }
    }
</style>
@endpush

@section('content')
@php $b = $sale->branch; $cur = $b->currency ?: 'Rs.'; $m = fn ($n) => $cur.' '.number_format($n, 2); @endphp
<div class="receipt" id="receipt">
    <div class="c">
        @if($b->logo_url)<img src="{{ $b->logo_url }}" class="logo" alt="logo"><br>@endif
        <div class="shop">{{ $b->name }}</div>
        @if($b->address)<div class="muted">{{ $b->address }}</div>@endif
        @if($b->phone)<div class="muted">Tel: {{ $b->phone }}</div>@endif
        @if($b->email)<div class="muted">{{ $b->email }}</div>@endif
        @if($b->tax_no)<div class="muted">Tax/VAT No: {{ $b->tax_no }}</div>@endif
    </div>
    <hr>
    <div class="title">Sales Receipt</div>
    <div class="meta">
        <span>Invoice</span><span>{{ $sale->invoice_no }}</span>
        <span>Date</span><span>{{ $sale->created_at->format('d M Y, H:i') }}</span>
        <span>Cashier</span><span>{{ $sale->user->name }}</span>
        <span>Customer</span><span>{{ $sale->customer?->name ?? 'Walk-in' }}</span>
    </div>
    <hr style="margin-bottom:4px">
    <table>
        <thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th></tr></thead>
        @foreach($sale->items as $i)
            <tr><td>{{ $i->product->name }}</td><td class="r">{{ $i->quantity }}</td><td class="r">{{ number_format($i->unit_price, 2) }}</td><td class="r">{{ number_format($i->quantity * $i->unit_price, 2) }}</td></tr>
            @if($i->discount > 0)<tr><td colspan="3" class="sub r">Discount</td><td class="sub r">-{{ number_format($i->discount, 2) }}</td></tr>@endif
        @endforeach
    </table>
    <hr>
    <div class="row2"><span>Subtotal</span><span>{{ $m($sale->subtotal) }}</span></div>
    @if($sale->discount > 0)<div class="row2"><span>Total discount</span><span>-{{ $m($sale->discount) }}</span></div>@endif
    <div class="total"><span>TOTAL</span><span>{{ $m($sale->total) }}</span></div>
    @foreach($sale->payments as $p)
        <div class="row2"><span>Paid ({{ ucfirst($p->method) }})</span><span>{{ $m($p->amount) }}</span></div>
    @endforeach
    @if($sale->paid - $sale->total > 0)
        <div class="row2"><span>Cash tendered</span><span>{{ $m($sale->paid - $sale->payments->where('method', '!=', 'cash')->sum('amount')) }}</span></div>
        <div class="row2"><span>Change</span><span>{{ $m($sale->paid - $sale->total) }}</span></div>
    @endif
    <hr>
    <div class="foot">{{ $b->receipt_footer ?: 'Thank you for shopping with us!' }}</div>
    <div class="c muted" style="margin-top:2px">Powered by JAAN Network</div>
</div>
@endsection

@push('scripts')
@if(request()->boolean('print'))
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
@endif
@endpush
