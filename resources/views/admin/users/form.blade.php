@extends('layouts.app')
@section('title', $user->exists ? 'Edit Login' : 'New Login')

@section('content')
<form method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card shadow-sm" style="max-width:620px">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Email (login)</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="col-md-6"><label class="form-label">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label><input type="text" name="password" class="form-control" minlength="6" @required(! $user->exists)></div>
        @unless($user->isAdmin())
        <div class="col-md-6"><label class="form-label">Branch</label>
            <select name="branch_id" class="form-select" required>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $user->branch_id) == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </div>
        @endunless
        @if($user->exists && $user->id !== auth()->id())
            <div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="a" @checked(old('is_active', $user->is_active))><label class="form-check-label" for="a">Active (can sign in)</label></div>
        @endif
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.users.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
