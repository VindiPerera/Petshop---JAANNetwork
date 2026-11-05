@extends('layouts.app')
@section('title', 'Sales Report')
@section('actions')<button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>@endsection

@section('content')
@include('partials.filters', ['dates' => true])
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-indigo"><i class="bi bi-graph-up-arrow"></i></div><div><div class="lbl">Revenue</div><div class="val">{{ number_format($summary->revenue, 2) }}</div></div></div></div></div>
    <div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-green"><i class="bi bi-receipt"></i></div><div><div class="lbl">Bills</div><div class="val">{{ $summary->bills }}</div></div></div></div></div>
    <div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-amber"><i class="bi bi-percent"></i></div><div><div class="lbl">Discounts given</div><div class="val">{{ number_format($summary->discounts, 2) }}</div></div></div></div></div>
</div>
<div class="row g-3">
    @if(auth()->user()->isAdmin())
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">By branch</div><table class="table table-sm mb-0">
        <thead><tr><th>Branch</th><th class="text-end">Bills</th><th class="text-end">Revenue</th></tr></thead>
        @forelse($byBranch as $r)<tr><td>{{ $r->name }}</td><td class="text-end">{{ $r->bills }}</td><td class="text-end">{{ number_format($r->revenue, 2) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>@endforelse
    </table></div></div>
    @endif
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">By payment method</div><table class="table table-sm mb-0">
        <thead><tr><th>Method</th><th class="text-end">Bills</th><th class="text-end">Revenue</th></tr></thead>
        @forelse($byPayment as $r)<tr><td class="text-capitalize">{{ $r->payment_method }}</td><td class="text-end">{{ $r->bills }}</td><td class="text-end">{{ number_format($r->revenue, 2) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>@endforelse
    </table></div></div>
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Top products</div><table class="table table-sm mb-0">
        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead>
        @forelse($topProducts as $r)<tr><td>{{ $r->name }}</td><td class="text-end">{{ $r->qty }}</td><td class="text-end">{{ number_format($r->revenue, 2) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>@endforelse
    </table></div></div>
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Daily sales</div><table class="table table-sm mb-0">
        <thead><tr><th>Date</th><th class="text-end">Bills</th><th class="text-end">Revenue</th></tr></thead>
        @forelse($daily as $r)<tr><td>{{ $r->day }}</td><td class="text-end">{{ $r->bills }}</td><td class="text-end">{{ number_format($r->revenue, 2) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>@endforelse
    </table></div></div>
</div>
@endsection
