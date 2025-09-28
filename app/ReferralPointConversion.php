<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferralPointConversion extends Model
{
    //

 protected $fillable = [
        'user_id',
        'aggregate_point',
        'amount_conversion',
    ];

}
