<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_id',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'amount' => 'decimal:2',
    ];
}
