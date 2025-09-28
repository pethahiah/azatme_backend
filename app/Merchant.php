<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    //
protected $fillable = [
        'tax_id_number',
        'business_registration_number',
        'business_name',
    	'uuid_code',
    	'user_id',
    ];

}
