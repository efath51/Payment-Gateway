<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayCurrency extends Model
{
    protected $casts = [
        'gateway_parameter' => 'object',
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'fixed_charge' => 'decimal:8',
        'percent_charge' => 'decimal:8',
        'rate' => 'decimal:8'
    ];

    public function method()
    {
        return $this->belongsTo(Gateway::class, 'method_code', 'code');
    }
}
