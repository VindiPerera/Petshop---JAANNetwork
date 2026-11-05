@extends('layouts.app')
@section('title', 'Stock Value Report')
@section('actions')<button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>@endsection

@section('content')
@include('partials.filters')
<div class="card shadow-sm"><table class="table mb-0">
    <thead><tr><th>Location</th><th>Type</th><th class="text-end">Units</th><th class="text-end">Value at cost</th><th class="text-end">Value at retail</th></tr></thead>
    @forelse($rows as $r)
        <tr><td>{{ $r->name }}</td><td class="text-capitalize">{{ $r->type }}</td><td class="text-end">{{ number_format($r->units) }}</td><td class="text-end">{{ number_format($r->cost_value, 2) }}</td><td class="text-end">{{ number_format($r->retail_value, 2) }}</td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No stock.</td></tr>
    @endforelse
    @if($rows->count() > 1)
        <tfoot><tr><th colspan="2">Total</th><th class="text-end">{{ number_format($rows->sum('units')) }}</th><th class="text-end">{{ number_format($rows->sum('cost_value'), 2) }}</th><th class="text-end">{{ number_format($rows->sum('retail_value'), 2) }}</th></tr></tfoot>
    @endif
</table></div>
@endsection
