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
