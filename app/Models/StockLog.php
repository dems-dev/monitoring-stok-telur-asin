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
        'amount',
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
}
