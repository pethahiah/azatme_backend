<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Donor extends Model
{
    //

	 protected $fillable = [
        'payThruReference',
        'transactionReference',
        'fiName',
        'status',
        'amount',
        'responseCode',
        'paymentMethod',
        'commission',
        'residualAmount',
        'resultCode',
        'responseDescription',
        'providedEmail',
        'providedName',
        'remarks',
    ];

}
