<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'unit_id',
        'price',
        'purchase_price',
        'stock',
        'min_stock',
        'nearest_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'unit_id' => 'integer',
            'price' => 'integer',
            'purchase_price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'nearest_expiry_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
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

    /** Mendekati kedaluwarsa (<= $days hari atau sudah lewat). */
    public function isExpiringSoon(int $days = 3): bool
    {
        return $this->nearest_expiry_date !== null && $this->nearest_expiry_date <= today()->addDays($days);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 3): Builder
    {
        return $query->whereNotNull('nearest_expiry_date')
            ->where('nearest_expiry_date', '<=', today()->addDays($days));
    }

    /** Produk sudah pernah dimutasi (masuk / keluar / opname). */
    public function hasMovements(): bool
    {
        return $this->stockLogs()->exists();
    }

    /** Harga beli belum diisi, sehingga nilai persediaannya tidak bisa dihitung. */
    public function isMissingPurchasePrice(): bool
    {
        return $this->purchase_price <= 0;
    }
}
