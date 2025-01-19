<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivePayment extends Model
{
    //		
    protected $fillable = ['paymentReference', 'product_id', 'product_type'];

    public function paymentable(): MorphTo
    {
        return $this->morphTo(null, 'product_type', 'product_id', 'paymentReference');
    }

}
