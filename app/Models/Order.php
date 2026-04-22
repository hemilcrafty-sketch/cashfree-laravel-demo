<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'status',
        'customer_email',
        'customer_phone',
        'payment_id',
    ];
}
