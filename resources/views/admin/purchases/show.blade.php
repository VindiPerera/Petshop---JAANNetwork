@extends('layouts.app')
@section('title', 'Receipt '.$purchase->purchase_no)
@section('actions')<button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><div class="text-muted small">Supplier</div>{{ $purchase->supplier?->name ?? '—' }}</div>
            <div class="col-md-4"><div class="text-muted small">Date</div>{{ $purchase->received_date->format('Y-m-d') }}</div>
            <div class="col-md-4"><div class="text-muted small">Received by</div>{{ $purchase->user->name }}</div>
        </div>
        <table class="table">
            <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Line total</th></tr></thead>
            @foreach($purchase->items as $i)
                <tr><td>{{ $i->product->sku }}</td><td>{{ $i->product->name }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ number_format($i->unit_cost, 2) }}</td><td class="text-end">{{ number_format($i->quantity * $i->unit_cost, 2) }}</td></tr>
            @endforeach
            <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end">{{ number_format($purchase->total_cost, 2) }}</th></tr></tfoot>
        </table>
        @if($purchase->note)<p class="text-muted mb-0">Note: {{ $purchase->note }}</p>@endif
    </div>
</div>
@endsection
