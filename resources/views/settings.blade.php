@extends('layouts.app')
@section('title', 'Shop Settings')

@section('content')
<form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="row g-3">
    @csrf
    <div class="col-lg-7">
        <div class="card"><div class="card-header">Details printed on your bills</div>
            <div class="card-body row g-3">
                <div class="col-md-8"><label class="form-label">Shop name</label><input name="name" class="form-control" value="{{ old('name', $branch->name) }}" required></div>
                <div class="col-md-4"><label class="form-label">Currency symbol</label><input name="currency" class="form-control" value="{{ old('currency', $branch->currency) }}" required></div>
                <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $branch->address) }}"></div>
                <div class="col-md-6"><label class="form-label">Phone number(s)</label><input name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}" placeholder="e.g. 011 2345678 / 077 1234567"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $branch->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Tax / VAT / Business reg. no.</label><input name="tax_no" class="form-control" value="{{ old('tax_no', $branch->tax_no) }}"></div>
                <div class="col-md-6"><label class="form-label">Bill footer message</label><input name="receipt_footer" class="form-control" value="{{ old('receipt_footer', $branch->receipt_footer) }}" placeholder="Thank you! Visit again."></div>
                <div class="col-12">
                    <label class="form-label">Shop logo (PNG/JPG, max 1 MB)</label>
                    <input type="file" name="logo" accept="image/*" class="form-control">
                    @if($branch->logo_url)
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <img src="{{ $branch->logo_url }}" style="max-height:60px;max-width:140px" alt="logo">
                            <label class="form-check"><input type="checkbox" class="form-check-input" name="remove_logo" value="1"> Remove logo</label>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save settings</button></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Bill preview</div>
            <div class="card-body text-center" style="font-family:'Courier New',monospace;font-size:12px">
                @if($branch->logo_url)<img src="{{ $branch->logo_url }}" style="max-height:60px;max-width:140px" alt="logo"><br>@endif
                <div class="fw-bold" style="font-size:15px">{{ $branch->name }}</div>
                <div>{{ $branch->address ?: 'Your address' }}</div>
                <div>Tel: {{ $branch->phone ?: 'Your phone' }}</div>
                @if($branch->email)<div>{{ $branch->email }}</div>@endif
                @if($branch->tax_no)<div>Tax/VAT No: {{ $branch->tax_no }}</div>@endif
                <hr>
                <div class="text-start">Dog Food Adult 3kg<br>&nbsp;&nbsp;1 x 3,200.00 <span class="float-end">3,200.00</span></div>
                <hr>
                <div class="fw-bold d-flex justify-content-between"><span>TOTAL</span><span>{{ $branch->currency }} 3,200.00</span></div>
                <hr>
                <div>{{ $branch->receipt_footer ?: 'Thank you for shopping with us!' }}</div>
            </div>
        </div>
    </div>
</form>
@endsection
