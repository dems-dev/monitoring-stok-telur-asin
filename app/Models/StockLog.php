<?php

namespace App\Models;

use App\Enums\LogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'product_id',
        'user_id',
        'supplier_id',
        'amount',
        'stok_sebelum',
        'stok_sesudah',
        'source_or_target',
        'status',
        'note',
        'logged_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => LogType::class,
            'amount' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
            'logged_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        // withTrashed agar laporan tetap lengkap setelah produk dihapus lunak.
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
