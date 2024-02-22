<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    //
	protected $fillable = [
        'email',
        'last_name',
        'phone_number',
        'first_name',
        'issue'
    ];
}
