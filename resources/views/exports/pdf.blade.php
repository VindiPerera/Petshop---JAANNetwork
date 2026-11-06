<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #3730a3; }
        .sub { color: #64748b; margin-bottom: 14px; }
        h2 { font-size: 12px; margin: 16px 0 5px; color: #1e1b4b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #e0e7ff; color: #312e81; text-align: left; padding: 5px 6px; font-size: 9px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        tr.tot td { font-weight: bold; background: #f1f5f9; border-top: 1px solid #94a3b8; }
        .r { text-align: right; }
        .foot { margin-top: 18px; color: #94a3b8; font-size: 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="sub">{{ $subtitle }}</div>

    @foreach($sections as $s)
        @if(!empty($s['heading']))<h2>{{ $s['heading'] }}</h2>@endif
        <table>
            <thead><tr>
                @foreach($s['columns'] as $i => $c)<th class="{{ ($s['align'][$i] ?? 'left') === 'right' ? 'r' : '' }}">{{ $c }}</th>@endforeach
            </tr></thead>
            <tbody>
            @forelse($s['rows'] as $row)
                <tr>@foreach($row as $i => $cell)<td class="{{ ($s['align'][$i] ?? 'left') === 'right' ? 'r' : '' }}">{{ is_float($cell) ? number_format($cell, 2) : $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($s['columns']) }}" style="text-align:center;color:#94a3b8">No data</td></tr>
            @endforelse
            @if(!empty($s['totals']))
                <tr class="tot">@foreach($s['totals'] as $i => $cell)<td class="{{ ($s['align'][$i] ?? 'left') === 'right' ? 'r' : '' }}">{{ is_float($cell) ? number_format($cell, 2) : $cell }}</td>@endforeach</tr>
            @endif
            </tbody>
        </table>
    @endforeach

    <div class="foot">Generated {{ now()->format('Y-m-d H:i') }} · {{ config('app.name') }}</div>
</body>
</html>
