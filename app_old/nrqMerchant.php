<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class nrqMerchant extends Model
{
    //

	protected $fillable = [
        'name',
        'tin',
	'contact',
	'phone',
	'address',
	'email',
	'bankNo',
	'accountName',
	'accountNumber',
	'referenceCode',
	'remarks',
	'merchantNumber',
        'auth_id',
	'qrcode'
    ];



}
