<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function stockLogs(): HasMany
    {
        return $this->hasMany(StockLog::class);
    }

    /** Alias agar cocok dengan prompt (nama, telepon, alamat). */
    protected function telepon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone,
            set: fn ($value) => ['phone' => $value],
        );
    }

    protected function alamat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->address,
            set: fn ($value) => ['address' => $value],
        );
    }
}
