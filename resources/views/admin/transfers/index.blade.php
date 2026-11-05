@extends('layouts.app')
@section('title', 'GRN / Transfers')
@section('actions')<a href="{{ route('admin.transfers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New GRN</a>@endsection

@section('content')
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>GRN No.</th><th>Date</th><th>To shop</th><th>Items</th><th>By</th></tr></thead>
    @forelse($transfers as $t)
        <tr><td><a href="{{ route('admin.transfers.show', $t) }}">{{ $t->grn_no }}</a></td><td>{{ $t->created_at->format('Y-m-d H:i') }}</td><td>{{ $t->to->name }}</td><td>{{ $t->items_count }}</td><td>{{ $t->user->name }}</td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No transfers yet.</td></tr>
    @endforelse
</table></div>
<div class="mt-3">{{ $transfers->links() }}</div>
@endsection
