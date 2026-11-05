<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; margin: 0; }
        .split { display: flex; min-height: 100vh; }
        .hero { flex: 1.1; background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 55%, #6366f1 100%); color: #fff; display: flex; flex-direction: column; justify-content: center; padding: 4rem; position: relative; overflow: hidden; }
        .hero::before, .hero::after { content: ''; position: absolute; border-radius: 50%; background: rgba(255,255,255,.07); }
        .hero::before { width: 420px; height: 420px; right: -120px; top: -120px; }
        .hero::after { width: 300px; height: 300px; left: -90px; bottom: -90px; }
        .hero .logo { width: 64px; height: 64px; border-radius: 18px; background: rgba(255,255,255,.16); display: grid; place-items: center; font-size: 2rem; margin-bottom: 1.5rem; }
        .hero h1 { font-weight: 700; font-size: 2.4rem; letter-spacing: -.02em; }
        .hero li { margin-bottom: .6rem; color: #c7d2fe; }
        .hero li i { color: #a5b4fc; margin-right: .5rem; }
        .form-side { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; background: #f5f7fb; }
        .form-box { width: 100%; max-width: 380px; }
        .form-control { border-radius: .65rem; padding: .7rem .9rem; border-color: #dbe1ec; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 .22rem rgba(99,102,241,.15); }
        .btn-login { background: #6366f1; border: 0; color: #fff; border-radius: .65rem; padding: .75rem; font-weight: 600; box-shadow: 0 8px 20px rgba(99,102,241,.35); }
        .btn-login:hover { background: #4f46e5; color: #fff; }
        @media (max-width: 860px) { .hero { display: none; } }
    </style>
</head>
<body>
<div class="split">
    <div class="hero">
        <div class="logo">🐾</div>
        <h1>{{ config('app.name') }}</h1>
        <p class="fs-5 mb-4" style="color:#c7d2fe">Warehouse, branches and billing in one place.</p>
        <ul class="list-unstyled">
            <li><i class="bi bi-check-circle-fill"></i>Central warehouse &amp; GRN transfers</li>
            <li><i class="bi bi-check-circle-fill"></i>Fast POS billing for every shop</li>
            <li><i class="bi bi-check-circle-fill"></i>Live inventory, sales &amp; profit reports</li>
        </ul>
    </div>
    <div class="form-side">
        <div class="form-box">
            <h3 class="fw-bold mb-1">Welcome back</h3>
            <p class="text-secondary mb-4">Sign in to your admin panel or shop POS.</p>
            <form method="post" action="{{ route('login') }}">@csrf
                <div class="mb-3">
                    <label class="form-label fw-medium small">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="you@example.com" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" id="r"><label class="form-check-label small" for="r">Keep me signed in</label></div>
                <button class="btn btn-login w-100">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
