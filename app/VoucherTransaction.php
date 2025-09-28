<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VoucherTransaction extends Model
{

         protected $fillable = [
        'voucher_id',
        'beneficiary_id',
        'merchant_id',
        'amount',
	'payout_status',
	'redeemed_at',
        'status',
        'type',
        'code_generation_method',
        'longitude',
        'latitude',
        'state',
        'city',
        'device'
];
}
