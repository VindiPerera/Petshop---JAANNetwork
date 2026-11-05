<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #6366f1; --brand-dark: #4f46e5; --brand-soft: #eef2ff;
            --ink: #0f172a; --muted: #64748b; --line: #e8ecf3; --bg: #f5f7fb;
            --bs-body-font-family: 'Inter', system-ui, sans-serif; --bs-body-color: var(--ink); --bs-primary: var(--brand);
            --bs-border-radius: .65rem; --bs-link-color: var(--brand-dark); --bs-link-hover-color: var(--brand);
        }
        body { background: var(--bg); font-size: .92rem; -webkit-font-smoothing: antialiased; }
        h4 { font-weight: 700; letter-spacing: -.01em; }

        /* Sidebar */
        .sidebar { width: 252px; height: 100vh; position: sticky; top: 0; overflow-y: auto; background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%); padding: .75rem .75rem 1rem; }
        .sidebar .brand { display: flex; align-items: center; gap: .6rem; color: #fff; font-weight: 700; font-size: 1.1rem; padding: .6rem .5rem; }
        .sidebar .brand span.logo { width: 38px; height: 38px; border-radius: 11px; background: rgba(255,255,255,.14); display: grid; place-items: center; font-size: 1.2rem; }
        .sidebar .me { margin: .4rem 0 .6rem; padding: .65rem .8rem; border-radius: .8rem; background: rgba(255,255,255,.08); color: #fff; font-size: .8rem; line-height: 1.3; }
        .sidebar .me small { color: #a5b4fc; }
        .sidebar a.nav-i { color: #c7d2fe; text-decoration: none; padding: .52rem .8rem; display: flex; align-items: center; gap: .65rem; border-radius: .6rem; margin-bottom: 2px; transition: all .15s; }
        .sidebar a.nav-i i { font-size: 1.05rem; opacity: .85; }
        .sidebar a.nav-i:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar a.nav-i.active { background: #fff; color: var(--brand-dark); font-weight: 600; box-shadow: 0 4px 14px rgba(0,0,0,.18); }
        .sidebar a.nav-i.active i { opacity: 1; }
        .sidebar .section { font-size: .66rem; text-transform: uppercase; letter-spacing: .1em; color: #818cf8; padding: 1rem .8rem .3rem; font-weight: 600; }
        .sidebar .logout { width: 100%; border: 1px solid rgba(255,255,255,.25); color: #e0e7ff; background: transparent; border-radius: .6rem; padding: .45rem; margin-top: 1rem; }
        .sidebar .logout:hover { background: rgba(255,255,255,.12); color: #fff; }

        main { padding: 1.75rem 2rem !important; }

        /* Cards / tables */
        .card { border: 1px solid var(--line) !important; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 20px rgba(15,23,42,.04) !important; }
        .card-header { background: #fff !important; border-bottom: 1px solid var(--line); border-radius: 1rem 1rem 0 0 !important; padding: .9rem 1.2rem; font-weight: 600; }
        .card-footer { border-top: 1px solid var(--line); border-radius: 0 0 1rem 1rem !important; padding: .9rem 1.2rem; }
        .card-body { padding: 1.2rem; }
        .card > .table, .card > table { margin-bottom: 0; }
        .card > .table:last-child, .card > table:last-child { border-radius: 0 0 1rem 1rem; overflow: hidden; }
        .table { --bs-table-hover-bg: #f8faff; }
        .table > thead th { background: #f8fafc; color: var(--muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; border-bottom: 1px solid var(--line); padding: .8rem 1rem; white-space: nowrap; }
        .table > tbody td, .table > tfoot th, .table > tfoot td { padding: .75rem 1rem; border-color: var(--line); vertical-align: middle; }
        .table-sm > :not(caption) > * > * { padding: .55rem 1rem; }

        /* Forms / buttons */
        .form-control, .form-select { border-color: #dbe1ec; border-radius: .6rem; padding: .55rem .8rem; }
        .form-control-sm, .form-select-sm { padding: .35rem .65rem; }
        .form-control:focus, .form-select:focus { border-color: var(--brand); box-shadow: 0 0 0 .22rem rgba(99,102,241,.15); }
        .form-label { font-weight: 500; color: #334155; font-size: .82rem; margin-bottom: .3rem; }
        .btn { border-radius: .6rem; font-weight: 500; padding: .5rem 1rem; }
        .btn-sm { padding: .3rem .7rem; }
        .btn-primary { background: var(--brand); border-color: var(--brand); box-shadow: 0 4px 12px rgba(99,102,241,.3); }
        .btn-primary:hover, .btn-primary:active { background: var(--brand-dark) !important; border-color: var(--brand-dark) !important; }
        .btn-warning { background: #f59e0b; border-color: #f59e0b; color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,.3); }
        .btn-warning:hover { background: #d97706; border-color: #d97706; color: #fff; }
        .btn-success { background: #10b981; border-color: #10b981; box-shadow: 0 4px 12px rgba(16,185,129,.3); }
        .btn-success:hover { background: #059669; border-color: #059669; }
        .btn-outline-secondary { border-color: #dbe1ec; color: #475569; }
        .btn-outline-secondary:hover { background: #f1f5f9; color: var(--ink); border-color: #cbd5e1; }
        .btn-outline-primary { color: var(--brand-dark); border-color: #c7d2fe; }
        .btn-outline-primary:hover { background: var(--brand-soft); color: var(--brand-dark); border-color: #c7d2fe; }
        .badge { font-weight: 600; padding: .4em .7em; border-radius: 2rem; }
        .text-bg-success { background: #d1fae5 !important; color: #047857 !important; }
        .text-bg-secondary { background: #e2e8f0 !important; color: #475569 !important; }
        .text-bg-info { background: #e0e7ff !important; color: #4338ca !important; }
        .text-bg-dark { background: #1e1b4b !important; color: #fff !important; }
        .alert { border: 0; border-radius: .8rem; }
        .page-link { border-radius: .5rem !important; margin: 0 2px; border-color: var(--line); }
        .active > .page-link { background: var(--brand); border-color: var(--brand); }

        /* Stat tiles */
        .stat { border-radius: 1rem; position: relative; overflow: hidden; }
        .stat .card-body { display: flex; align-items: center; gap: 1rem; }
        .stat .ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; font-size: 1.3rem; flex-shrink: 0; }
        .ico-indigo { background: #e0e7ff; color: #4f46e5; } .ico-green { background: #d1fae5; color: #059669; }
        .ico-amber { background: #fef3c7; color: #d97706; } .ico-rose { background: #ffe4e6; color: #e11d48; }
        .stat .lbl { color: var(--muted); font-size: .76rem; font-weight: 500; }
        .stat .val { font-size: 1.35rem; font-weight: 700; letter-spacing: -.02em; line-height: 1.2; }

        /* POS */
        #grid button { border-radius: .8rem; border-color: var(--line) !important; background: #fff; padding: .7rem .8rem; transition: all .15s; }
        #grid button:not(:disabled):hover { border-color: var(--brand) !important; box-shadow: 0 6px 18px rgba(99,102,241,.18); transform: translateY(-2px); color: var(--ink); }
        #grid button:disabled { opacity: .5; }

        @media (max-width: 991px) { .sidebar { display: none; } main { padding: 1rem !important; } }
        @media print { .sidebar, .no-print { display: none !important; } body { background: #fff; } .card { box-shadow: none !important; } }
    </style>
    @stack('head')
</head>
<body>
@php
    $u = auth()->user();
    $nav = fn ($pattern) => request()->routeIs($pattern) ? 'active' : '';
@endphp
<div class="d-flex">
    <nav class="sidebar flex-shrink-0 no-print">
        <div class="brand"><span class="logo">🐾</span> {{ config("app.name") }}</div>
        <div class="me"><strong>{{ $u->name }}</strong><br><small>{{ $u->isAdmin() ? "Main Admin" : $u->branch->name }}</small></div>

        <a class="nav-i {{ $nav('dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a>

        @if($u->isAdmin())
            <div class="section">Warehouse</div>
            <a class="nav-i {{ $nav('admin.purchases.*') }}" href="{{ route('admin.purchases.index') }}"><i class="bi bi-box-arrow-in-down"></i>Receive Stock</a>
            <a class="nav-i {{ $nav('admin.transfers.*') }}" href="{{ route('admin.transfers.index') }}"><i class="bi bi-truck"></i>GRN / Transfers</a>
            <div class="section">Setup</div>
            <a class="nav-i {{ $nav('admin.branches.*') }}" href="{{ route('admin.branches.index') }}"><i class="bi bi-shop"></i>Branches</a>
            <a class="nav-i {{ $nav('admin.users.*') }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i>Shop Logins</a>
            <a class="nav-i {{ $nav('admin.products.*') }}" href="{{ route('admin.products.index') }}"><i class="bi bi-tags"></i>Products</a>
            <a class="nav-i {{ $nav('admin.categories.*') }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-diagram-3"></i>Categories</a>
            <a class="nav-i {{ $nav('admin.suppliers.*') }}" href="{{ route('admin.suppliers.index') }}"><i class="bi bi-person-lines-fill"></i>Suppliers</a>
        @else
            <div class="section">Shop</div>
            <a class="nav-i {{ $nav('pos') }}" href="{{ route('pos') }}"><i class="bi bi-calculator"></i>POS Billing</a>
        @endif

        <div class="section">Operations</div>
        <a class="nav-i {{ $nav('sales.*') }}" href="{{ route('sales.index') }}"><i class="bi bi-receipt"></i>Sales</a>
        <a class="nav-i {{ $nav('customers.*') }}" href="{{ route('customers.index') }}"><i class="bi bi-person-heart"></i>Customers</a>
        <a class="nav-i {{ $nav('inventory.*') }}" href="{{ route('inventory.index') }}"><i class="bi bi-boxes"></i>Inventory</a>

        <div class="section">Reports</div>
        <a class="nav-i {{ $nav('reports.sales') }}" href="{{ route('reports.sales') }}"><i class="bi bi-graph-up"></i>Sales Report</a>
        <a class="nav-i {{ $nav('reports.profit') }}" href="{{ route('reports.profit') }}"><i class="bi bi-cash-coin"></i>Profit Report</a>
        <a class="nav-i {{ $nav('reports.stock') }}" href="{{ route('reports.stock') }}"><i class="bi bi-clipboard-data"></i>Stock Value</a>

        <form method="post" action="{{ route('logout') }}">@csrf
            <button class="logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
        </form>
    </nav>

    <main class="flex-grow-1 p-4" style="min-width:0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">@yield('title')</h4>
            <div class="no-print">@yield('actions')</div>
        </div>
        @if(session('ok'))<div class="alert alert-success py-2 no-print">{{ session('ok') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-danger py-2 no-print"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
