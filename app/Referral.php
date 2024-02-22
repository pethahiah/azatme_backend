<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method checkSettingEnquiry(string $modelType)
 */
class Referral extends Model
{
    //
    protected $fillable = [
        'user_id',
        'ref_url',
        'ref_code',
        'ref_by',
        'point',
        'product'
    ];
}
