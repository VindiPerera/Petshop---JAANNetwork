@extends('layouts.app')
@section('title', 'Profit Report')
@section('actions')<button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>@endsection

@section('content')
@include('partials.filters', ['dates' => true, 'export' => true])
<div class="card shadow-sm"><table class="table mb-0">
    <thead><tr><th>Branch</th><th class="text-end">Revenue</th><th class="text-end">Cost of goods</th><th class="text-end">Gross profit</th><th class="text-end">Margin</th></tr></thead>
    @forelse($rows as $r)
        @php $p = $r->revenue - $r->cost; @endphp
        <tr><td>{{ $r->name }}</td><td class="text-end">{{ number_format($r->revenue, 2) }}</td><td class="text-end">{{ number_format($r->cost, 2) }}</td>
            <td class="text-end fw-bold {{ $p < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($p, 2) }}</td><td class="text-end">{{ $r->revenue > 0 ? number_format($p / $r->revenue * 100, 1) : 0 }}%</td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No sales in this period.</td></tr>
    @endforelse
    @if($rows->count() > 1)
        @php $rev = $rows->sum('revenue'); $cost = $rows->sum('cost'); @endphp
        <tfoot><tr><th>Total</th><th class="text-end">{{ number_format($rev, 2) }}</th><th class="text-end">{{ number_format($cost, 2) }}</th><th class="text-end">{{ number_format($rev - $cost, 2) }}</th><th></th></tr></tfoot>
    @endif
</table></div>
<p class="text-muted small mt-2">Cost is the product cost price recorded at the moment of each sale.</p>
@endsection
