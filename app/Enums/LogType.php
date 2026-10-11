<?php

namespace App\Enums;

enum LogType: string
{
    case MASUK = 'MASUK';
    case KELUAR = 'KELUAR';

    /** Arah pengaruh terhadap stok: +1 menambah, -1 mengurangi. */
    public function sign(): int
    {
        return $this === self::MASUK ? 1 : -1;
    }

    /** Status yang sah untuk tiap jenis mutasi, mengikuti prototipe. */
    public function allowedStatuses(): array
    {
        return match ($this) {
            self::MASUK => ['Selesai'],
            self::KELUAR => ['Terjual', 'Distribusi Cabang', 'Rusak/Pecah', 'Expired'],
        };
    }
}
