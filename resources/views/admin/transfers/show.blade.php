@extends('layouts.app')
@section('title', 'GRN '.$transfer->grn_no)
@section('actions')<button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><div class="text-muted small">From</div>{{ $transfer->from->name }}</div>
            <div class="col-md-3"><div class="text-muted small">To</div>{{ $transfer->to->name }}</div>
            <div class="col-md-3"><div class="text-muted small">Date</div>{{ $transfer->created_at->format('Y-m-d H:i') }}</div>
            <div class="col-md-3"><div class="text-muted small">Issued by</div>{{ $transfer->user->name }}</div>
        </div>
        <table class="table">
            <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Quantity</th></tr></thead>
            @foreach($transfer->items as $i)
                <tr><td>{{ $i->product->sku }}</td><td>{{ $i->product->name }}</td><td class="text-end">{{ $i->quantity }}</td></tr>
            @endforeach
            <tfoot><tr><th colspan="2" class="text-end">Total units</th><th class="text-end">{{ $transfer->items->sum('quantity') }}</th></tr></tfoot>
        </table>
        @if($transfer->note)<p class="text-muted mb-0">Note: {{ $transfer->note }}</p>@endif
    </div>
</div>
@endsection
