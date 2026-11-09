@extends('layouts.app')
@section('title', $expense->exists ? 'Edit Expense' : 'Add Expense')

@section('content')
@php $type = old('type', $expense->type); @endphp
<form method="post" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="card shadow-sm" style="max-width:820px">@csrf
    @if($expense->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label d-block">Expense type</label>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="type" id="t-single" value="single" @checked($type === 'single')>
                <label class="btn btn-outline-primary" for="t-single">Normal (one date)</label>
                <input type="radio" class="btn-check" name="type" id="t-range" value="range" @checked($type === 'range')>
                <label class="btn btn-outline-primary" for="t-range">Range (covers several days)</label>
            </div>
            <div class="form-text">A range expense (e.g. 3 months of rent) is spread evenly across its days in the reports.</div>
        </div>
        <div class="row g-3">
            @if(auth()->user()->isAdmin())
                <div class="col-md-4"><label class="form-label req">Location</label>
                    <select name="branch_id" class="form-select" required>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $expense->branch_id) == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-4"><label class="form-label req">Category</label>
                <select name="category" class="form-select" required>@foreach(\App\Models\Expense::CATEGORIES as $c)<option @selected(old('category', $expense->category) === $c)>{{ $c }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label req">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required></div>
            <div class="col-md-8"><label class="form-label req">Title</label><input name="title" class="form-control" value="{{ old('title', $expense->title) }}" required></div>
            <div class="col-md-4" id="preset-wrap"><label class="form-label">Covers</label>
                <select id="preset" class="form-select">
                    <option value="">Custom dates</option>
                    <option value="w1">1 week</option>
                    <option value="m1">1 month (monthly)</option>
                    <option value="m3">3 months (quarterly)</option>
                    <option value="m6">6 months</option>
                    <option value="m12">1 year (yearly)</option>
                </select></div>
            <div class="col-md-4"><label class="form-label req" id="start-label">Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($expense->start_date)->toDateString()) }}" required></div>
            <div class="col-md-4" id="end-wrap"><label class="form-label req">End date</label><input type="date" name="end_date" id="end" class="form-control" value="{{ old('end_date', $expense->type === 'range' ? optional($expense->end_date)->toDateString() : '') }}"></div>
            <div class="col-12"><label class="form-label">Note</label><input name="note" class="form-control" value="{{ old('note', $expense->note) }}"></div>
        </div>
    </div>
    <div class="card-footer bg-white"><button class="btn btn-primary">{{ $expense->exists ? 'Save changes' : 'Save expense' }}</button> <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const radios = document.querySelectorAll('input[name=type]'), wrap = document.getElementById('end-wrap'), end = document.getElementById('end'), label = document.getElementById('start-label');
    const preset = document.getElementById('preset'), presetWrap = document.getElementById('preset-wrap'), start = document.querySelector('input[name=start_date]');
    const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    function sync() {
        const range = document.getElementById('t-range').checked;
        wrap.classList.toggle('d-none', !range); presetWrap.classList.toggle('d-none', !range);
        end.required = range; end.disabled = !range;
        label.textContent = range ? 'Start date' : 'Date';
    }
    // End date = start + length - 1 day (e.g. 1 Jan + 1 month -> 31 Jan; 1 Jan + 1 year -> 31 Dec).
    function applyPreset() {
        if (!preset.value || !start.value) return;
        const [y, m, d] = start.value.split('-').map(Number), e = new Date(y, m - 1, d);
        if (preset.value === 'w1') e.setDate(e.getDate() + 6);
        else { const months = +preset.value.slice(1); e.setMonth(e.getMonth() + months, d); if (e.getDate() !== d) e.setDate(0); e.setDate(e.getDate() - 1); }
        end.value = iso(e);
    }
    radios.forEach(r => r.addEventListener('change', sync));
    preset.addEventListener('change', applyPreset);
    start.addEventListener('change', applyPreset);
    end.addEventListener('input', () => { preset.value = ''; });
    sync();
})();
</script>
@endpush
