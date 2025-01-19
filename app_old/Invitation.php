<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    //
	 protected $fillable = [
        'email',
        'token',
        'inviter_id',
        'name',
        'ajo_id',
        'amount',
        'phone_number',
        'position',
        'transactionDate',
        'merchantReference',
        'fiName',
        'paymentMethod',
        'linkExpireDateTime',
        'payThruReference',
        'paymentReference',
        'responseCode',
        'responseDescription',
        'amount_paid',
        'commission',
        'residualAmount',
        'account_name',
        'resultCode',
        'uidd',
        'minus_residual',
        'first_name',
	'stat'
        
    ];
	 public function paymentDate()
    {
        return $this->hasOne(PaymentDate::class, 'invitation_id');
    }

	public function Decline()
    {
        return $this->hasOne(Decline::class, 'invitation_id');
    }


}
