<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentDate extends Model
{
    //
protected $fillable = [
        'payment_date',
        'invitation_id',
	'position',
	'collection_date'
        
    ];



//    public function paymentDates()
//{
  //  return $this->hasMany(PaymentDate::class, 'invitation_id');
//}

public function invitation()
    {
        return $this->belongsTo(Invitation::class, 'invitation_id');
    }

}
