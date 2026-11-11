<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'checkout_key', 'payment_method', 'payment_status',
        'total_amount', 'cash_received', 'change_amount', 'paid_at', 'midtrans_token', 'midtrans_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'cash_received' => 'integer',
            'change_amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}




