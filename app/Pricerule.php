<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pricerule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'price_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'shop_name','compaign_name', 'line_no', 'rule_type', 'rule_title', 'rule_value','rule_qty',
    ];
}
