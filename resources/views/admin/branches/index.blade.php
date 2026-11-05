@extends('layouts.app')
@section('title', 'Branches')
@section('actions')<a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Branch</a>@endsection

@section('content')
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Phone</th><th>Logins</th><th>Status</th><th></th></tr></thead>
    @foreach($branches as $b)
        <tr>
            <td>{{ $b->code }}</td><td>{{ $b->name }}<div class="small text-muted">{{ $b->address }}</div></td>
            <td><span class="badge text-bg-{{ $b->type === 'warehouse' ? 'dark' : 'info' }}">{{ ucfirst($b->type) }}</span></td>
            <td>{{ $b->phone }}</td><td>{{ $b->users_count }}</td>
            <td>{!! $b->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' !!}</td>
            <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.branches.edit', $b) }}">Edit</a>
                @if($b->type === 'shop')
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('sales.index', ['branch_id' => $b->id]) }}">Sales</a>
                @endif
                <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.index', ['branch_id' => $b->id]) }}">Stock</a>
            </td>
        </tr>
    @endforeach
</table></div>
@endsection
