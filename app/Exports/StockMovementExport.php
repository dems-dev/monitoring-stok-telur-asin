<?php

namespace App\Exports;

use App\Models\StockLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Collection $logs;
    protected int $rowNumber = 0;

    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Tipe',
            'SKU',
            'Nama Produk',
            'Jumlah',
            'Satuan',
            'Stok Sebelum',
            'Stok Sesudah',
            'Sumber / Tujuan',
            'Status',
            'Catatan',
            'Dicatat Oleh',
        ];
    }

    /**
     * @param StockLog $log
     */
    public function map($log): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $log->logged_date->format('Y-m-d'),
            $log->type->value,
            $log->product?->sku ?? '-',
            $log->product?->name ?? 'Produk Dihapus',
            $log->amount,
            $log->product?->unit?->name ?? 'butir',
            $log->stok_sebelum,
            $log->stok_sesudah,
            $log->supplier?->name ?? $log->source_or_target ?? '-',
            $log->status ?? '-',
            $log->note ?? '-',
            $log->user?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'], // Amber light
                ],
            ],
        ];
    }
}
