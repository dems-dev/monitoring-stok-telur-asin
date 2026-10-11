<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case PETUGAS = 'PETUGAS';
    case OWNER = 'OWNER';

    /** Label yang tampil di badge dan header, mengikuti prototipe. */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Super Admin',
            self::PETUGAS => 'Petugas Gudang',
            self::OWNER => 'Pemilik Toko',
        };
    }

    /** Kelas Tailwind untuk avatar peran. */
    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'bg-slate-800',
            self::PETUGAS => 'bg-emerald-600',
            self::OWNER => 'bg-amber-600',
        };
    }

    /** OWNER hanya boleh membaca; ADMIN dan PETUGAS boleh mencatat mutasi. */
    public function canRecordMutation(): bool
    {
        return $this !== self::OWNER;
    }

    /** Hanya ADMIN yang mengelola master produk dan user. */
    public function canManageMasterData(): bool
    {
        return $this === self::ADMIN;
    }
}
