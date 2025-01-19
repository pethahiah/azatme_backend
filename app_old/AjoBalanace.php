<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjoBalanace extends Model
{
    //
	 protected $fillable = [
        'user_id',
        'balance',
	'action',
    ];
}
