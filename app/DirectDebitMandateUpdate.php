<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DirectDebitMandateUpdate extends Model
{
    //

    protected $fillable =
        [
            'mandateId',
            'requestType',
            'amountLimit'
        ];
}
