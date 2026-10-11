<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\StockLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            'logs' => $this->filtered($filters)->paginate(25)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    /**
     * Ekspor CSV mengikuti filter yang aktif. Prototipe selalu mengekspor
     * seluruh log meski filter terpasang; perilaku itu diperbaiki di sini.
     */
    public function export(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();

        // reorder() membuang urutan laporan: chunkById menuntut urutan menurut id,
        // dan untuk buku besar urutan id menaik justru yang dibaca orang.
        $query = $this->filtered($filters)->reorder();

        $filename = 'Laporan_Stok_Telur_Asin_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            // BOM UTF-8 agar Excel di Windows membaca huruf beraksen dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'Tanggal', 'Tipe', 'SKU', 'Nama Produk',
                'Jumlah', 'Sumber/Tujuan', 'Status', 'Catatan', 'Dicatat Oleh',
            ]);

            // Dialirkan per potongan supaya laporan besar tidak memenuhi memori.
            $query->with(['product', 'user'])->chunkById(500, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->logged_date->format('Y-m-d'),
                        $log->type->value,
                        $log->product?->sku,
                        $log->product?->name,
                        $log->amount,
                        $log->source_or_target,
                        $log->status,
                        $log->note,
                        $log->user?->name,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @param  array<string, mixed>  $filters */
    private function filtered(array $filters): Builder
    {
        return StockLog::query()
            ->with('product')
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['start_date'] ?? null, fn (Builder $q, $date) => $q->where('logged_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $q, $date) => $q->where('logged_date', '<=', $date))
            ->latest('logged_date')
            ->latest('id');
    }
}
