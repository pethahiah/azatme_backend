<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tokenn extends Model
{
    //

	protected $fillable = [
        'token',
        'expiration_date'
    ];
}
