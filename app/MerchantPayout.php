<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantPayout extends Model
{
    //

protected $fillable = [
'merchant_id',
'amount',
'status',
'transaction_reference',
'paid_at',
];

}
