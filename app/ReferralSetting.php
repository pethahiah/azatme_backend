<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static find($referralId)
 * @method static where(string $string, $referralId)
 * @method static create(array $array)
 * @method static whereNotNull(string $string)
 * @method static orderBy(string $string, string $string1)
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
        'start_date',
        'duration',
        'referral_active_point',
        'product_getting_point'

    ];
}
