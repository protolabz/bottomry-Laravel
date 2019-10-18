<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BQSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'BQSettings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop','text', 'currency', 'cartText',
    ];
}
