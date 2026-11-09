@extends('layouts.app')
@section('title', 'Expenses')
@section('actions')<a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Expense</a>@endsection

@section('content')
@include('partials.filters', ['dates' => true, 'search' => true])
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-rose"><i class="bi bi-wallet2"></i></div><div><div class="lbl">Expenses in period</div><div class="val">{{ number_format($total, 2) }}</div></div></div></div></div>
    <div class="col-md-8"><div class="card shadow-sm"><div class="card-body py-2">
        <div class="small text-muted mb-1">By category (range expenses are spread over their days)</div>
        @forelse($byCategory as $c => $v)<span class="badge text-bg-secondary me-1">{{ $c }}: {{ number_format($v, 2) }}</span>@empty<span class="text-muted">No expenses.</span>@endforelse
    </div></div></div>
</div>
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Date / range</th>@if(auth()->user()->isAdmin())<th>Location</th>@endif<th class="text-end">Amount</th><th class="text-end">In period</th><th></th></tr></thead>
    @forelse($expenses as $e)
        <tr>
            <td>{{ $e->title }}@if($e->note)<div class="small text-muted">{{ $e->note }}</div>@endif</td>
            <td>{{ $e->category }}</td>
            <td><span class="badge text-bg-{{ $e->type === 'range' ? 'info' : 'secondary' }}">{{ $e->type === 'range' ? 'Range' : 'Normal' }}</span></td>
            <td class="text-nowrap">{{ $e->start_date->format('Y-m-d') }}@if($e->type === 'range') → {{ $e->end_date->format('Y-m-d') }}@endif</td>
            @if(auth()->user()->isAdmin())<td>{{ $e->branch->name }}</td>@endif
            <td class="text-end">{{ number_format($e->amount, 2) }}</td>
            <td class="text-end">{{ number_format($e->allocatedBetween($from, $to), 2) }}</td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('expenses.edit', $e) }}"><i class="bi bi-pencil"></i> Edit</a>
                <form method="post" action="{{ route('expenses.destroy', $e) }}" class="d-inline" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No expenses in this period.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $expenses->links() }}</div>
@endsection
