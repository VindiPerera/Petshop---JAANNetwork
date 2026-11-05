@extends('layouts.app')
@section('title', 'Shop Logins')
@section('actions')<a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Login</a>@endsection

@section('content')
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Status</th><th></th></tr></thead>
    @foreach($users as $u)
        <tr>
            <td>{{ $u->name }}</td><td>{{ $u->email }}</td>
            <td>{{ $u->isAdmin() ? 'Main Admin' : 'Shop' }}</td><td>{{ $u->branch?->name ?? '—' }}</td>
            <td>{!! $u->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' !!}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.edit', $u) }}">Edit</a></td>
        </tr>
    @endforeach
</table></div>
@endsection
