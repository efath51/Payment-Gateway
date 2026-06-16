<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Models\Gateway;

class Deposit extends Model
{
       protected $casts = [
        'detail' => 'object',
        'amount' => 'decimal:8',
        'charge' => 'decimal:8',
        'rate' => 'decimal:8',
        'final_amount' => 'decimal:8'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'method_code', 'code');
    }

    public function gatewayCurrency()
    {
        return GatewayCurrency::where('method_code', $this->method_code)
                              ->where('currency', $this->method_currency)
                              ->first();
    }

    // Scopes for different statuses
    public function scopeInitiated($query)
    {
        return $query->where('status', Status::PAYMENT_INITIATE);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', Status::PAYMENT_SUCCESS);
    }

    public function scopePending($query)
    {
        return $query->where('status', Status::PAYMENT_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', Status::PAYMENT_REJECT);
    }
}
