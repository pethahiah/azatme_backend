<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KontributeBalance extends Model
{
    //
	 protected $fillable = [
        'user_id',
        'balance',
	'action',
    ];
}
