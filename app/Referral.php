<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method checkSettingEnquiry(string $modelType)
 * @method static where(string $string, $id)
 * @method static create(array $data)
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
        'product',
        'end_date',
        'referral_duration',
        'status',
    ];


}
