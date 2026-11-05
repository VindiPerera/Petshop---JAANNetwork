@extends('layouts.app')
@section('title', 'New GRN — Warehouse to Shop')

@section('content')
<form method="post" action="{{ route('admin.transfers.store') }}" class="card shadow-sm">@csrf
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Send to shop</label>
                <select name="to_branch_id" class="form-select" required><option value="">Select shop…</option>
                    @foreach($shops as $s)<option value="{{ $s->id }}" @selected(old('to_branch_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Note</label><input name="note" class="form-control" value="{{ old('note') }}"></div>
        </div>
        <p class="text-muted small">Add as many products as you need — they all go out on one GRN.</p>
        @include('partials.items_editor', ['withCost' => false, 'showStock' => true])
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Create GRN &amp; transfer stock</button> <a href="{{ route('admin.transfers.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
