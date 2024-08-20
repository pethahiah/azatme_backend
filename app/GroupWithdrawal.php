<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupWithdrawal extends Model
{
    //
    protected $fillable = [
        'account_name',
        'account_number',
        'description',
        'group_id',
        'beneficiary_id',
        'amount',
        'bank',
	'charges',
	'uniqueId',
	'minus_residual',
	'status'
    ];
}
