<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Data Kelas Digital — {{ date('d-m-Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 9pt; color: #222; padding: 20pt; }
        h1 { font-size: 14pt; text-align: center; margin-bottom: 2pt; }
        .meta { text-align: center; font-size: 8pt; color: #666; margin-bottom: 16pt; }
        .section { margin-bottom: 14pt; page-break-inside: avoid; }
        .section-title { font-size: 10pt; font-weight: bold; background: #4182b3; color: #fff; padding: 4pt 6pt; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        th, td { border: 1px solid #ccc; padding: 3pt 5pt; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #ddebf7; font-weight: bold; font-size: 8pt; }
        td { font-size: 8pt; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .empty { color: #999; font-style: italic; padding: 4pt 6pt; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>Laporan Data Kelas Digital</h1>
    <p class="meta">
        Dicetak pada {{ date('d-m-Y H:i') }}
        @if ($tahunAjaran)
            &mdash; Filter: <strong>{{ $tahunAjaran }}</strong>
        @endif
        &mdash; {{ $totalRows }} baris data dari {{ count($datasets) }} entitas
    </p>

    @foreach ($datasets as $dataset)
        <div class="section">
            <div class="section-title">{{ $dataset['title'] }} ({{ count($dataset['rows']) }})</div>
            <table>
                <thead>
                    <tr>
                        @foreach ($dataset['headers'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataset['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($dataset['headers']) }}" class="empty">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
