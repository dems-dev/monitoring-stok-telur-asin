<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /** Kategori bawaan dari prototipe; bebas ditambah lewat form. */
    public const CATEGORIES = [
        'Original',
        'Bakar',
        'Asap',
        'Bumbu/Rasa',
        'Kemasan',
        'Mentah',
    ];

    protected $fillable = [
        'sku',
        'name',
        'category',
        'price',
        'stock',
        'min_stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    public function stockLogs(): HasMany
    {
        return $this->hasMany(StockLog::class);
    }

    /** Stok menyentuh atau turun di bawah batas minimum. */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }
}
