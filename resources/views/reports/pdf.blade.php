<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Persediaan Stok Telur Asin</title>
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
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 10px;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
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
            padding: 6px 5px;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8.5px;
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
            margin-top: 30px;
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
        <h3 class="report-title">Laporan Riwayat Mutasi Persediaan Barang</h3>
    </div>

    <!-- METADATA & PARAMETER FILTER -->
    <table class="meta-table">
        <tr>
            <td style="width: 15%;"><b>Periode</b></td>
            <td style="width: 35%;">: {{ $filters['start_date'] ?? 'Awal' }} s/d {{ $filters['end_date'] ?? 'Sekarang' }}</td>
            <td style="width: 15%;"><b>Dicetak Oleh</b></td>
            <td style="width: 35%;">: {{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</td>
        </tr>
        <tr>
            <td><b>Jenis Mutasi</b></td>
            <td>: {{ $filters['type'] ?? 'Semua Jenis (Masuk / Keluar / Penyesuaian)' }}</td>
            <td><b>Waktu Cetak</b></td>
            <td>: {{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
        @if (!empty($filters['product_name']) || !empty($filters['user_name']))
            <tr>
                <td><b>Filter Tambahan</b></td>
                <td colspan="3">:
                    @if (!empty($filters['product_name'])) Produk: <b>{{ $filters['product_name'] }}</b> @endif
                    @if (!empty($filters['user_name'])) | Petugas: <b>{{ $filters['user_name'] }}</b> @endif
                </td>
            </tr>
        @endif
    </table>

    <!-- TABEL DATA MUTASI -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 65px;" class="text-center">Tanggal</th>
                <th style="width: 65px;" class="text-center">Tipe</th>
                <th style="width: 65px;">SKU</th>
                <th>Nama Produk</th>
                <th style="width: 50px;" class="text-right">Jumlah</th>
                <th style="width: 40px;">Satuan</th>
                <th>Sumber / Tujuan</th>
                <th style="width: 65px;">Status</th>
                <th style="width: 70px;">Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center font-mono">{{ $log->logged_date->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ strtolower($log->type->value) }}">
                            {{ $log->type->value }}
                        </span>
                    </td>
                    <td class="font-mono">{{ $log->product?->sku ?? '-' }}</td>
                    <td><b>{{ $log->product?->name ?? 'Produk Dihapus' }}</b></td>
                    <td class="text-right font-mono">
                        {{ match($log->type) {
                            \App\Enums\LogType::MASUK => '+',
                            \App\Enums\LogType::KELUAR => '-',
                            \App\Enums\LogType::PENYESUAIAN => '±',
                        } }}{{ number_format($log->amount, 0, ',', '.') }}
                    </td>
                    <td>{{ $log->product?->unit?->name ?? 'butir' }}</td>
                    <td>{{ $log->supplier?->name ?? $log->source_or_target ?? '-' }}</td>
                    <td>{{ $log->status }}</td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Tidak ada riwayat mutasi barang pada periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN -->
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
                <b>Penanggung Jawab Persediaan</b>
                <div class="signature-space"></div>
                <b>{{ auth()->user()->name }}</b><br>
                <span style="font-size: 9px; color: #64748b;">NIP / ID: {{ auth()->user()->username }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
