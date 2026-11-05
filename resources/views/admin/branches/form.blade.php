@extends('layouts.app')
@section('title', $branch->exists ? 'Edit Branch' : 'New Branch')

@section('content')
<form method="post" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card shadow-sm" style="max-width:720px">
    @csrf @if($branch->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-8"><label class="form-label">Branch name</label><input name="name" class="form-control" value="{{ old('name', $branch->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control text-uppercase" value="{{ old('code', $branch->code) }}" required></div>
        <div class="col-md-8"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $branch->address) }}"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}"></div>

        @if($branch->exists)
            @if($branch->type === 'shop')
            <div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="a" @checked(old('is_active', $branch->is_active))><label class="form-check-label" for="a">Active</label></div>
            @endif
        @else
            <div class="col-12"><hr><h6>Shop login (used to sign in to the POS)</h6></div>
            <div class="col-md-4"><label class="form-label">Login name</label><input name="login_name" class="form-control" value="{{ old('login_name') }}" required></div>
            <div class="col-md-4"><label class="form-label">Login email</label><input type="email" name="login_email" class="form-control" value="{{ old('login_email') }}" required></div>
            <div class="col-md-4"><label class="form-label">Password</label><input type="text" name="login_password" class="form-control" minlength="6" required></div>
        @endif
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.branches.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
