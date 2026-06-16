<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'charge',
        'post_balance',
        'trx_type',
        'trx',
        'details',
        'remark'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'charge' => 'decimal:8',
        'post_balance' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDeposit($query)
    {
        return $query->where('remark', 'deposit');
    }

    public function scopePayment($query)
    {
        return $query->where('remark', 'payment');
    }

    public function scopePlus($query)
    {
        return $query->where('trx_type', '+');
    }

    public function scopeMinus($query)
    {
        return $query->where('trx_type', '-');
    }
}
