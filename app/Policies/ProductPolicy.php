<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /** Semua peran boleh melihat katalog dan stok. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Boleh mengelola master produk sama sekali, tanpa menunjuk produk
     * tertentu. Dipakai view untuk memutuskan kolom aksi tampil atau tidak;
     * update/delete tidak bisa dipanggil dengan string kelas karena menuntut
     * instance Product.
     */
    public function manageAny(User $user): bool
    {
        return $user->role->canManageMasterData();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageMasterData();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role->canManageMasterData();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->role->canManageMasterData();
    }
}
