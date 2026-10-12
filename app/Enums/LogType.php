<?php

namespace App\Enums;

enum LogType: string
{
    case MASUK = 'MASUK';
    case KELUAR = 'KELUAR';
    case PENYESUAIAN = 'PENYESUAIAN';

    /** Arah pengaruh terhadap stok: +1 menambah, -1 mengurangi, 0 penyesuaian langsung. */
    public function sign(): int
    {
        return match ($this) {
            self::MASUK => 1,
            self::KELUAR => -1,
            self::PENYESUAIAN => 0,
        };
    }

    /** Status yang sah untuk tiap jenis mutasi, mengikuti prototipe dan kebutuhan opname. */
    public function allowedStatuses(): array
    {
        return match ($this) {
            self::MASUK => ['Selesai'],
            self::KELUAR => ['Terjual', 'Distribusi Cabang', 'Rusak/Pecah', 'Expired'],
            self::PENYESUAIAN => ['Stock Opname', 'Selisih Kurang', 'Selisih Lebih', 'Sesuai'],
        };
    }
}
