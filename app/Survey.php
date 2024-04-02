<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Survey extends Model
{
    //
    protected $fillable = [
        'timestamp',
        'email',
        'content',
        'gender',
        'address',
        'age',
        'occupation',
        'sector',
        'wk_bholiday_commute',
        'isTrans_issue',
        'trate_wk_bholiday2',
        'currentT_rate_wk_bholiday',
        'consent_rideshare_toDestination',
        'will_urideshare_onbholiday_wk',
        'can_you_offer_rideshare',
        'impact_ofshortageTrans_WkHoliday_onWork',
        'publicTrans_normalDay',
        'publicTrans_bholiday',
        'publicTrans_wk',
    ];
}
