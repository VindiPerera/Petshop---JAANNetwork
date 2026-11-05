@extends('layouts.app')
@section('title', 'Categories')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <form method="post" action="{{ route('admin.categories.store') }}" class="card shadow-sm">@csrf
            <div class="card-body"><label class="form-label">New category</label><input name="name" class="form-control" required></div>
            <div class="card-footer bg-white"><button class="btn btn-primary">Add</button></div>
        </form>
    </div>
    <div class="col-md-8"><div class="card shadow-sm"><table class="table mb-0">
        <thead><tr><th>Name</th><th>Products</th><th></th></tr></thead>
        @foreach($categories as $c)
            <tr><td>{{ $c->name }}</td><td>{{ $c->products_count }}</td>
                <td class="text-end"><form method="post" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
        @endforeach
    </table></div></div>
</div>
@endsection
