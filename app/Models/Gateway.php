<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
     protected $casts = [
        'gateway_parameters' => 'object',
        'status' => 'integer'
    ];

    protected $hidden = ['gateway_parameters'];


     public function currencies()
    {
        return $this->hasMany(GatewayCurrency::class, 'method_code', 'code');
    }

    public function singleCurrency()
    {
        return $this->hasOne(GatewayCurrency::class, 'method_code', 'code');
    }

    // Scopes
    public function scopeAutomatic($query)
    {
        return $query->where('code', '<', Status::AUTOMATIC_GATEWAY_LIMIT);
    }

    public function scopeManual($query)
    {
        return $query->where('code', '>=', Status::AUTOMATIC_GATEWAY_LIMIT);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }
}
