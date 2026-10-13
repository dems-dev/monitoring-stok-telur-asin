<?php

namespace App\Http\Controllers;

use App\Enums\LogType;
use App\Exports\StockCardExport;
use App\Exports\StockMovementExport;
use App\Http\Requests\ReportFilterRequest;
use App\Http\Requests\StockCardFilterRequest;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            'logs' => $this->filtered($filters)->paginate(25)->withQueryString(),
            'filters' => $filters,
            'products' => Product::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Ekspor Laporan Riwayat Mutasi ke PDF ber-kop resmi.
     */
    public function exportPdf(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $logs = $this->filtered($filters)->get();

        if (! empty($filters['product_id'])) {
            $filters['product_name'] = Product::find($filters['product_id'])?->name;
        }
        if (! empty($filters['user_id'])) {
            $filters['user_name'] = User::find($filters['user_id'])?->name;
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'logs' => $logs,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        $filename = 'Laporan_Mutasi_Stok_Telur_Asin_'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Ekspor Laporan Riwayat Mutasi ke Microsoft Excel (.xlsx).
     */
    public function exportExcel(ReportFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $logs = $this->filtered($filters)->get();

        $filename = 'Laporan_Mutasi_Stok_Telur_Asin_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new StockMovementExport($logs), $filename);
    }

    /**
     * Ekspor CSV (Streaming) mengikuti filter yang aktif.
     */
    public function exportCsv(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $query = $this->filtered($filters)->reorder();

        $filename = 'Laporan_Stok_Telur_Asin_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            // BOM UTF-8 agar Excel di Windows membaca karakter lokal dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'Tanggal', 'Tipe', 'SKU', 'Nama Produk',
                'Jumlah', 'Satuan', 'Stok Sebelum', 'Stok Sesudah',
                'Sumber/Tujuan', 'Status', 'Catatan', 'Dicatat Oleh',
            ]);

            $query->chunkById(500, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->logged_date->format('Y-m-d'),
                        $log->type->value,
                        $log->product?->sku ?? '-',
                        $log->product?->name ?? 'Produk dihapus',
                        $log->amount,
                        $log->product?->unit?->name ?? 'butir',
                        $log->stok_sebelum,
                        $log->stok_sesudah,
                        $log->supplier?->name ?? $log->source_or_target ?? '-',
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

    /**
     * Alias backward-compatible untuk endpoint /laporan/export.
     */
    public function export(ReportFilterRequest $request): StreamedResponse
    {
        return $this->exportCsv($request);
    }

    /**
     * Halaman Buku Besar Kartu Stok per Produk.
     */
    public function stockCard(StockCardFilterRequest $request): View
    {
        $filters = $request->validated();
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();

        $selectedProduct = null;
        if (! empty($filters['product_id'])) {
            $selectedProduct = $products->firstWhere('id', (int) $filters['product_id']);
        } elseif ($products->isNotEmpty()) {
            $selectedProduct = $products->first();
            $filters['product_id'] = $selectedProduct->id;
        }

        $data = $this->calculateStockCardData($selectedProduct, $filters);

        return view('reports.stock_card', [
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'filters' => $filters,
            'movements' => $data['movements'],
            'summary' => $data['summary'],
        ]);
    }

    /**
     * Ekspor Kartu Stok per Produk ke PDF resmi.
     */
    public function exportStockCardPdf(StockCardFilterRequest $request): Response
    {
        $filters = $request->validated();
        $product = Product::with(['category', 'unit'])->findOrFail($filters['product_id']);

        $data = $this->calculateStockCardData($product, $filters);

        $pdf = Pdf::loadView('reports.stock_card_pdf', [
            'product' => $product,
            'filters' => $filters,
            'movements' => $data['movements'],
            'summary' => $data['summary'],
        ])->setPaper('a4', 'portrait');

        $filename = 'Kartu_Stok_'.$product->sku.'_'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Ekspor Kartu Stok per Produk ke Microsoft Excel (.xlsx).
     */
    public function exportStockCardExcel(StockCardFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $product = Product::with(['category', 'unit'])->findOrFail($filters['product_id']);

        $data = $this->calculateStockCardData($product, $filters);

        $filename = 'Kartu_Stok_'.$product->sku.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new StockCardExport($product, $data['movements'], $data['summary']['initial_stock']),
            $filename
        );
    }

    /**
     * Hitung pergerakan kartu stok dan saldo per tanggal.
     *
     * @param  array<string, mixed>  $filters
     * @return array{movements: Collection, summary: array<string, int>}
     */
    private function calculateStockCardData(?Product $product, array $filters): array
    {
        if (! $product) {
            return [
                'movements' => collect(),
                'summary' => [
                    'initial_stock' => 0,
                    'total_masuk' => 0,
                    'total_keluar' => 0,
                    'total_adjustments' => 0,
                    'final_stock' => 0,
                ],
            ];
        }

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        // Saldo awal: saldo stok sesudah transaksi terakhir sebelum rentang tanggal dimulai
        $initialStock = 0;
        if ($startDate) {
            $lastLogBefore = StockLog::where('product_id', $product->id)
                ->where('logged_date', '<', $startDate)
                ->latest('logged_date')
                ->latest('id')
                ->first();

            $initialStock = $lastLogBefore?->stok_sesudah ?? 0;
        } else {
            // Bila tanpa rentang awal, stok awal diambil dari stok_sebelum transaksi pertama
            $firstLog = StockLog::where('product_id', $product->id)
                ->oldest('logged_date')
                ->oldest('id')
                ->first();

            $initialStock = $firstLog?->stok_sebelum ?? 0;
        }

        // Ambil transaksi pergerakan stok dalam periode (diurutkan kronologis)
        $movements = StockLog::with(['user', 'supplier'])
            ->where('product_id', $product->id)
            ->when($startDate, fn (Builder $q) => $q->where('logged_date', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->where('logged_date', '<=', $endDate))
            ->orderBy('logged_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalMasuk = 0;
        $totalKeluar = 0;
        $totalAdjustments = 0;

        foreach ($movements as $log) {
            if ($log->type === LogType::MASUK) {
                $totalMasuk += $log->amount;
            } elseif ($log->type === LogType::KELUAR) {
                $totalKeluar += $log->amount;
            } elseif ($log->type === LogType::PENYESUAIAN) {
                $totalAdjustments += ($log->stok_sesudah - $log->stok_sebelum);
            }
        }

        $finalStock = $movements->isNotEmpty()
            ? $movements->last()->stok_sesudah
            : $initialStock;

        return [
            'movements' => $movements,
            'summary' => [
                'initial_stock' => $initialStock,
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'total_adjustments' => $totalAdjustments,
                'final_stock' => $finalStock,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        return StockLog::query()
            ->with(['product.unit', 'user', 'supplier'])
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['product_id'] ?? null, fn (Builder $q, $pid) => $q->where('product_id', $pid))
            ->when($filters['user_id'] ?? null, fn (Builder $q, $uid) => $q->where('user_id', $uid))
            ->when($filters['start_date'] ?? null, fn (Builder $q, $date) => $q->where('logged_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $q, $date) => $q->where('logged_date', '<=', $date))
            ->latest('logged_date')
            ->latest('id');
    }
}
