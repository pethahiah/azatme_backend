<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static find($referralId)
 * @method static where(string $string, $referralId)
 * @method static create(array $array)
 * @method static whereNotNull(string $string)
 */
class ReferralSetting extends Model
{
    //

    protected $fillable = [
        'admin_id',
        'point_limit',
        'point_conversion',
        'status',
        'end_date',
        'start_date'
    ];
}
