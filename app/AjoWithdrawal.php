<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjoWithdrawal extends Model
{
    //

	protected $fillable = [
        'accountName',
        'accountNumber',
        'description',
        'ajo_id',
        'beneficiary_id',
        'amount',
        'bank',
	'charges',
	'uniqueId',
	'status'
    ];
}
