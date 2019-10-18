<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderBottomry extends Model
{
    protected $table = 'orders';
    
    protected $fillable = [
        'orderDate', 'storeOrderNumber','bottomryOrderNumber','customerName','subTotal','shippingProtectionCost',
    ];
}
