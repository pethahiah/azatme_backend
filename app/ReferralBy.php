<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class ReferralBy extends Model
{
    protected $fillable = [
        'user_id',
        'ref_code',
        'referee_email',
        'referee_name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
