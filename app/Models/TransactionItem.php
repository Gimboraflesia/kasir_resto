<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'product_price', 'product_cost', 'quantity', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'integer',
            'product_cost' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
