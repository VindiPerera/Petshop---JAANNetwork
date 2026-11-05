@extends('layouts.app')
@section('title', 'Receive Stock into Warehouse')

@section('content')
<form method="post" action="{{ route('admin.purchases.store') }}" class="card shadow-sm">@csrf
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select"><option value="">—</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Received date</label><input type="date" name="received_date" class="form-control" value="{{ old('received_date', now()->toDateString()) }}" required></div>
            <div class="col-md-5"><label class="form-label">Note</label><input name="note" class="form-control" value="{{ old('note') }}"></div>
        </div>
        @include('partials.items_editor', ['withCost' => true, 'showStock' => false])
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">Receive stock</button> <a href="{{ route('admin.purchases.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
