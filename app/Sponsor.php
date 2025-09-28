<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Sponsor extends Model
{
    //

use SoftDeletes;
    protected $fillable = [
        'sponsor_name',
        'sponsor_registration_number',
        'sponsor_description',
        'isSponsorVerified',
        'user_id',
        'tin_number',
	'type'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
