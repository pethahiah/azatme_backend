<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invited extends Model
{
    //
protected $fillable = [
        'type',
	'email',
	'first_name',
	'last_name',
	'auth_id'
    ];


}
