<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'shopProducts';
    
    protected $fillable = [
        'shopName', 'shopToken','productID',
    ];
}
