<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DirectDebitProduct extends Model
{
    //
    protected $fillable = [
        'isPacketBased',
        'productName',
        'isUserResponsibleForCharges',
        'partialCollectionEnabled',
        'collectionAccountId',
        'productDescription',
        'classification',
        'remarks',
        'feeType',
    ];

}


