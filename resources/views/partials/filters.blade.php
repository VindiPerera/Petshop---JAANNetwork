{{-- Filter bar. Options: $dates (bool), $search (bool). Branch picker shows for admin only. --}}
<form class="card shadow-sm mb-3 no-print"><div class="card-body row g-2 align-items-end">
    @if(auth()->user()->isAdmin())
        <div class="col-md-3"><label class="form-label small mb-0">Branch</label>
            <select name="branch_id" class="form-select form-select-sm"><option value="">All branches</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
    @endif
    @if(!empty($dates))
        <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $from ?? request('from') }}" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $to ?? request('to') }}" class="form-control form-control-sm"></div>
    @endif
    @if(!empty($search))
        <div class="col-md-3"><label class="form-label small mb-0">Search</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm"></div>
    @endif
    <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button> <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Reset</a></div>
</div></form>
