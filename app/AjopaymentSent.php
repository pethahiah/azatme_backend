<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjopaymentSent extends Model
{
    //

	 protected $fillable = [
        'email',
        'status',
	'ajo_id',
	'date_sent'
    ];
}
