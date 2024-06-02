<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferralPoint extends Model
{
    //
    protected $fillable = [
        'user_id',
        'ref_code',
        'point',
        'product',
        'product_action',
    ];
}
