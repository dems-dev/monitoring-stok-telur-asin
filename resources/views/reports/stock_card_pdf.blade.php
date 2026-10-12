<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok - {{ $product->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 50px;
            height: 50px;
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            line-height: 50px;
            border-radius: 8px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .report-title-box {
            text-align: center;
            margin-bottom: 12px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .info-card {
            width: 100%;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 6px;
            margin-bottom: 14px;
            padding: 8px;
        }
        .info-table {
            width: 100%;
            font-size: 10px;
        }
        .info-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 6px;
            text-align: center;
        }
        .summary-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border: 1px solid #cbd5e1;
            padding: 6px 4px;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-masuk { background-color: #dcfce7; color: #15803d; }
        .badge-keluar { background-color: #ffe4e6; color: #be123c; }
        .badge-penyesuaian { background-color: #dbeafe; color: #1d4ed8; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .signatures-table {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 10px;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <!-- KOP LAPORAN RESMI -->
    <table class="header-table">
        <tr>
            <td style="width: 55px; vertical-align: middle;">
                <div class="header-logo">O</div>
            </td>
            <td style="vertical-align: middle;">
                <div class="header-title">SIM-STOK TELUR ASIN</div>
                <div class="header-subtitle">Sistem Monitoring Persediaan Barang Berbasis Web &middot; Sentra Telur Asin Brebes</div>
                <div class="header-subtitle">Jl. Diponegoro No. 12, Brebes, Jawa Tengah | Telp: (0283) 671234</div>
            </td>
            <td style="width: 150px; text-align: right; vertical-align: middle; font-size: 9px; color: #64748b;">
                Tanggal Dokumen:<br>
                <b>{{ now()->translatedFormat('d F Y') }}</b>
            </td>
        </tr>
    </table>

    <div class="report-title-box">
        <h3 class="report-title">KARTU PERSEDIAAN BARANG (STOCK CARD)</h3>
    </div>

    <!-- IDENTITAS PRODUK -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td style="width: 15%;"><b>Kode SKU</b></td>
                <td style="width: 35%;">: <span class="font-mono">{{ $product->sku }}</span></td>
                <td style="width: 15%;"><b>Kategori</b></td>
                <td style="width: 35%;">: {{ $product->category?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td><b>Nama Produk</b></td>
                <td>: <b>{{ $product->name }}</b></td>
                <td><b>Satuan Ukur</b></td>
                <td>: {{ $product->unit?->name ?? 'butir' }}</td>
            </tr>
            <tr>
                <td><b>Batas Minimum</b></td>
                <td>: {{ number_format($product->min_stock, 0, ',', '.') }} {{ $product->unit?->name ?? 'butir' }}</td>
                <td><b>Periode Laporan</b></td>
                <td>: {{ $filters['start_date'] ?? 'Awal' }} s/d {{ $filters['end_date'] ?? 'Sekarang' }}</td>
            </tr>
        </table>
    </div>

    <!-- RINGKASAN SALDO PERIODE -->
    <table class="summary-boxes">
        <tr>
            <td class="summary-box" style="width: 20%; background-color: #f1f5f9;">
                <div class="summary-label">Saldo Awal</div>
                <div class="summary-value font-mono">{{ number_format($summary['initial_stock'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%; background-color: #f0fdf4;">
                <div class="summary-label" style="color: #166534;">Total Masuk (+)</div>
                <div class="summary-value font-mono" style="color: #15803d;">+{{ number_format($summary['total_masuk'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%; background-color: #fff1f2;">
                <div class="summary-label" style="color: #9f1239;">Total Keluar (-)</div>
                <div class="summary-value font-mono" style="color: #be123c;">-{{ number_format($summary['total_keluar'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%; background-color: #eff6ff;">
                <div class="summary-label" style="color: #1e40af;">Penyesuaian (&plusmn;)</div>
                <div class="summary-value font-mono" style="color: #1d4ed8;">
                    {{ $summary['total_adjustments'] > 0 ? '+'.$summary['total_adjustments'] : $summary['total_adjustments'] }}
                </div>
            </td>
            <td class="summary-box" style="width: 20%; background-color: #fef3c7;">
                <div class="summary-label" style="color: #92400e;">Saldo Akhir</div>
                <div class="summary-value font-mono" style="color: #b45309;">{{ number_format($summary['final_stock'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <!-- BUKU BESAR MUTASI STOK -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 65px;" class="text-center">Tanggal</th>
                <th style="width: 55px;" class="text-center">Ref ID</th>
                <th style="width: 65px;" class="text-center">Tipe</th>
                <th>Sumber / Tujuan / Keterangan</th>
                <th style="width: 50px;" class="text-right">Masuk (+)</th>
                <th style="width: 50px;" class="text-right">Keluar (-)</th>
                <th style="width: 50px;" class="text-right">Opname (&plusmn;)</th>
                <th style="width: 55px;" class="text-right">Saldo</th>
                <th style="width: 65px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #f8fafc; font-style: italic;">
                <td class="text-center">-</td>
                <td class="text-center font-mono">{{ $filters['start_date'] ?? 'Awal' }}</td>
                <td class="text-center">-</td>
                <td class="text-center"><span class="badge" style="background:#e2e8f0; color:#475569;">SALDO AWAL</span></td>
                <td>Saldo awal sebelum pergerakan periode</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono"><b>{{ number_format($summary['initial_stock'], 0, ',', '.') }}</b></td>
                <td>Sistem</td>
            </tr>

            @forelse ($movements as $index => $log)
                @php
                    $isMasuk = $log->type === \App\Enums\LogType::MASUK;
                    $isKeluar = $log->type === \App\Enums\LogType::KELUAR;
                    $isOpname = $log->type === \App\Enums\LogType::PENYESUAIAN;
                    $diff = $isOpname ? ($log->stok_sesudah - $log->stok_sebelum) : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center font-mono">{{ $log->logged_date->format('Y-m-d') }}</td>
                    <td class="text-center font-mono" style="font-size: 8.5px;">#{{ $log->id }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ strtolower($log->type->value) }}">{{ $log->type->value }}</span>
                    </td>
                    <td>{{ $log->supplier?->name ?? $log->source_or_target ?? $log->note ?? '-' }}</td>
                    <td class="text-right font-mono text-emerald-600">
                        {{ $isMasuk ? '+'.number_format($log->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono text-rose-600">
                        {{ $isKeluar ? '-'.number_format($log->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono text-blue-600">
                        {{ $isOpname ? ($diff > 0 ? '+'.$diff : $diff) : '-' }}
                    </td>
                    <td class="text-right font-mono">
                        <b>{{ number_format($log->stok_sesudah, 0, ',', '.') }}</b>
                    </td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 12px; color: #94a3b8;">
                        Tidak ada transaksi mutasi untuk produk ini pada periode tersebut.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN -->
    <table class="signatures-table">
        <tr>
            <td>
                Mengetahui,<br>
                <b>Pemilik / Pimpinan Toko</b>
                <div class="signature-space"></div>
                <b>( .................................................... )</b>
            </td>
            <td>
                Brebes, {{ now()->translatedFormat('d F Y') }}<br>
                <b>Petugas Verifikasi Stok</b>
                <div class="signature-space"></div>
                <b>{{ auth()->user()->name }}</b><br>
                <span style="font-size: 9px; color: #64748b;">NIP / ID: {{ auth()->user()->username }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
