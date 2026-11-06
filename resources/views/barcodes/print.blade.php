<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Barcode labels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @php [$w, $h] = ['small' => [38, 25], 'medium' => [50, 30], 'large' => [70, 40]][$size]; @endphp
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', Arial, sans-serif; background: #e5e7eb; color: #000; }
        .bar { position: sticky; top: 0; background: #1e1b4b; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; }
        .bar button { background: #6366f1; border: 0; color: #fff; border-radius: 8px; padding: 7px 16px; font-weight: 600; cursor: pointer; }
        .sheet { display: flex; flex-wrap: wrap; gap: 3mm; padding: 5mm; }
        .label { width: {{ $w }}mm; height: {{ $h }}mm; background: #fff; padding: 1.2mm 1.5mm; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; text-align: center; page-break-inside: avoid; break-inside: avoid; }
        .label .nm { font-size: {{ $size === 'small' ? 6.5 : ($size === 'medium' ? 7.5 : 9.5) }}pt; font-weight: 600; line-height: 1.1; max-height: 2.3em; overflow: hidden; }
        .label .pr { font-size: {{ $size === 'small' ? 8 : ($size === 'medium' ? 9.5 : 12) }}pt; font-weight: 700; }
        .label svg { width: 100%; flex: 1 1 auto; min-height: 0; max-height: {{ $h * 0.5 }}mm; }
        .label .code { font-size: {{ $size === 'small' ? 5.5 : 6.5 }}pt; letter-spacing: .08em; font-variant-numeric: tabular-nums; }
        @media print {
            @page { size: {{ $w }}mm {{ $h }}mm; margin: 0; }
            body { background: #fff; }
            .bar { display: none; }
            .sheet { display: block; padding: 0; }
            .label { page-break-after: always; break-after: page; }
        }
    </style>
</head>
<body>
<div class="bar"><strong>{{ $labels->count() }} label(s) · {{ $w }} × {{ $h }} mm</strong>
    <span style="opacity:.8;font-size:13px">Set the printer paper to the label size, margins "None".</span>
    <button onclick="window.print()">Print</button></div>
<div class="sheet">
@foreach($labels as $p)
    <div class="label">
        @if($showName)<div class="nm">{{ $p->name }}</div>@endif
        {!! \App\Services\BarcodeService::svg($p->barcode, 60, 2) !!}
        <div class="code">{{ $p->barcode }}</div>
        @if($showPrice)<div class="pr">{{ $currency }} {{ number_format($p->sale_price, 2) }}</div>@endif
    </div>
@endforeach
</div>
</body>
</html>
