{{-- Filter bar. Options: $dates (bool), $search (bool), $export (bool). Branch picker shows for admin only. --}}
@php
    $presets = [
        'Today' => [today(), today()],
        'Yesterday' => [today()->subDay(), today()->subDay()],
        'Last 7 days' => [today()->subDays(6), today()],
        'Last week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
        'This month' => [now()->startOfMonth(), today()],
        'Last month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
    ];
    $q = request()->except(['export', 'page']);
@endphp
<form class="card mb-3 no-print"><div class="card-body py-3">
    <div class="row g-2 align-items-end">
        @if(auth()->user()->isAdmin())
            <div class="col-md-3"><label class="form-label">Branch</label>
                <select name="branch_id" class="form-select form-select-sm"><option value="">All branches</option>
                    @foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        @endif
        @if(!empty($dates))
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ $from ?? request('from') }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ $to ?? request('to') }}" class="form-control form-control-sm"></div>
        @endif
        @if(!empty($search))
            <div class="col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm"></div>
        @endif
        <div class="col-auto"><button class="btn btn-sm btn-primary">Apply</button> <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Reset</a></div>

        @if(!empty($export))
            <div class="col-auto ms-auto">
                <a class="btn btn-sm btn-outline-danger" href="{{ url()->current().'?'.http_build_query($q + ['export' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <a class="btn btn-sm btn-outline-success" href="{{ url()->current().'?'.http_build_query($q + ['export' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            </div>
        @endif
    </div>
    @if(!empty($dates))
        <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
            <span class="small text-muted me-1">Quick range:</span>
            @foreach($presets as $label => [$a, $z])
                <a class="btn btn-sm btn-light border" href="{{ url()->current().'?'.http_build_query(array_merge(request()->except(['from', 'to', 'page', 'export']), ['from' => $a->toDateString(), 'to' => $z->toDateString()])) }}">{{ $label }}</a>
            @endforeach
        </div>
    @endif
</div></form>
