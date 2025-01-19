<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OpenActive extends Model
{
    //

    protected $fillable = ['transactionReference', 'product_id', 'product_type'];

    public function paymentable(): MorphTo
    {
        return $this->morphTo(null, 'product_type', 'product_id', 'transactionReference');
    }
}
