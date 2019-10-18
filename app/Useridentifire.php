<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Useridentifire extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'Useridentifires';

    protected $fillable = [
    	'uuid', 'campaign_id', 
    ];

}
