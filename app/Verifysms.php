<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Verifysms extends Model
{
    //
    
    protected $dates = ['otp_expires_time'];
    
    protected $fillable = [
        'phone',
        'user_id',
        'otp',
        'email',
        'medium',
        'otp_expires_time'

    ];
}
