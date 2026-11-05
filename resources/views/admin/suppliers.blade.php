@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <form method="post" action="{{ route('admin.suppliers.store') }}" class="card shadow-sm">@csrf
            <div class="card-body">
                <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                <div><label class="form-label">Address</label><input name="address" class="form-control"></div>
            </div>
            <div class="card-footer bg-white"><button class="btn btn-primary">Add supplier</button></div>
        </form>
    </div>
    <div class="col-md-8"><div class="card shadow-sm"><table class="table mb-0">
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th></th></tr></thead>
        @foreach($suppliers as $s)
            <tr><td>{{ $s->name }}</td><td>{{ $s->phone }}</td><td>{{ $s->email }}</td>
                <td class="text-end"><form method="post" action="{{ route('admin.suppliers.destroy', $s) }}" onsubmit="return confirm('Delete this supplier?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
        @endforeach
    </table></div></div>
</div>
@endsection
