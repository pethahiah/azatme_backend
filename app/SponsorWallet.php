<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SponsorWallet extends Model
{
    //
protected $fillable = [
        'user_id',
        'reference',
        'wallet_balance',
    ];


}
