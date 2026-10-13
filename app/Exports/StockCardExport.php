<?php

namespace App\Exports;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockCardExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Product $product;

    protected Collection $rows;

    protected int $initialStock;

    protected int $rowNumber = 0;

    public function __construct(Product $product, Collection $rows, int $initialStock)
    {
        $this->product = $product;
        $this->rows = $rows;
        $this->initialStock = $initialStock;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'ID Transaksi',
            'Jenis Mutasi',
            'Sumber / Tujuan / Keterangan',
            'Masuk (+)',
            'Keluar (-)',
            'Penyesuaian (±)',
            'Saldo Akhir ('.($this->product->unit?->name ?? 'butir').')',
            'Petugas',
        ];
    }

    /**
     * @param  StockLog  $log
     */
    public function map($log): array
    {
        $this->rowNumber++;

        $masuk = $log->type === LogType::MASUK ? $log->amount : 0;
        $keluar = $log->type === LogType::KELUAR ? $log->amount : 0;
        $penyesuaian = $log->type === LogType::PENYESUAIAN ? ($log->stok_sesudah - $log->stok_sebelum) : 0;

        return [
            $this->rowNumber,
            $log->logged_date->format('Y-m-d'),
            '#LOG-'.$log->id,
            $log->type->value,
            $log->supplier?->name ?? $log->source_or_target ?? $log->note ?? '-',
            $masuk ?: '-',
            $keluar ?: '-',
            $penyesuaian !== 0 ? ($penyesuaian > 0 ? "+{$penyesuaian}" : "{$penyesuaian}") : '-',
            $log->stok_sesudah,
            $log->user?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0F2FE'], // Sky light
                ],
            ],
        ];
    }
}
