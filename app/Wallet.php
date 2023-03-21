<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    //

    protected $fillable = [
        'user_id',
        'charges',
        'residual_amount',
        'amount_paid_by_paythru',
        'balance',
        'amountExpectedRefundMe',
        'amountExpectedKontribute',
        'amountExpectedBusiness',

    ];
}
