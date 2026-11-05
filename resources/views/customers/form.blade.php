@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'New Customer')

@section('content')
<form method="post" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="card shadow-sm" style="max-width:720px">
    @csrf @if($customer->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $customer->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}"></div>
        <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $customer->address) }}"></div>
        <div class="col-md-6"><label class="form-label">Pet name</label><input name="pet_name" class="form-control" value="{{ old('pet_name', $customer->pet_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Pet type / breed</label><input name="pet_type" class="form-control" value="{{ old('pet_type', $customer->pet_type) }}"></div>
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Save</button> <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
