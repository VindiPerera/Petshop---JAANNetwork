@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-indigo"><i class="bi bi-currency-dollar"></i></div><div><div class="lbl">Today's Sales</div><div class="val">{{ number_format($todayTotal, 2) }}</div></div></div></div></div>
    <div class="col-md-3"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-green"><i class="bi bi-receipt"></i></div><div><div class="lbl">Today's Bills</div><div class="val">{{ $todayCount }}</div></div></div></div></div>
    <div class="col-md-3"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-amber"><i class="bi bi-calendar-check"></i></div><div><div class="lbl">This Month</div><div class="val">{{ number_format($monthTotal, 2) }}</div></div></div></div></div>
    @if(auth()->user()->isAdmin())
        <div class="col-md-3"><div class="card stat shadow-sm"><div class="card-body"><div class="ico ico-rose"><i class="bi bi-box-seam"></i></div><div><div class="lbl">Warehouse Stock Value (cost)</div><div class="val">{{ number_format($warehouseValue, 2) }}</div></div></div></div></div>
    @else
        <div class="col-md-3"><a href="{{ route('pos') }}" class="btn btn-warning w-100 h-100 d-flex align-items-center justify-content-center fs-5 rounded-4"><i class="bi bi-calculator me-2"></i>Open POS</a></div>
    @endif
</div>

@if(auth()->user()->isAdmin())
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Branch Performance</div>
    <table class="table mb-0">
        <thead><tr><th>Branch</th><th class="text-end">Today</th><th class="text-end">This Month</th><th class="text-end">Stock Value (cost)</th><th></th></tr></thead>
        <tbody>
        @forelse($branchSales as $row)
            <tr>
                <td>{{ $row['branch']->name }}</td>
                <td class="text-end">{{ number_format($row['today'], 2) }}</td>
                <td class="text-end">{{ number_format($row['month'], 2) }}</td>
                <td class="text-end">{{ number_format($row['stock_value'], 2) }}</td>
                <td class="text-end"><a href="{{ route('sales.index', ['branch_id' => $row['branch']->id]) }}">Sales</a> · <a href="{{ route('inventory.index', ['branch_id' => $row['branch']->id]) }}">Inventory</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No branches yet. <a href="{{ route('admin.branches.create') }}">Create one</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Low Stock</div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Product</th><th>Location</th><th class="text-end">Qty</th><th class="text-end">Reorder</th></tr></thead>
                @forelse($lowStock as $s)
                    <tr><td>{{ $s->name }}</td><td>{{ $s->branch }}</td><td class="text-end text-danger fw-bold">{{ $s->quantity }}</td><td class="text-end">{{ $s->reorder_level }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Nothing is running low.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Recent Sales</div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Invoice</th><th>Branch</th><th>Customer</th><th class="text-end">Total</th></tr></thead>
                @forelse($recent as $s)
                    <tr><td><a href="{{ route('sales.show', $s) }}">{{ $s->invoice_no }}</a></td><td>{{ $s->branch->name }}</td><td>{{ $s->customer?->name ?? 'Walk-in' }}</td><td class="text-end">{{ number_format($s->total, 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No sales yet.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
