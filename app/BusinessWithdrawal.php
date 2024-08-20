<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessWithdrawal extends Model
{
    //

    protected $fillable = [
        'account_name',
        'account_number',
        'description',
        'product_id',
        'beneficiary_id',
        'amount',
        'bank',
	'charges',
	'uniqueId',
	'minus_residual',
	'status'
    ];
}
