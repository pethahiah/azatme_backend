<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(mixed $productId, mixed $productId1)
 */
class DirectDebitProduct extends Model
{
    //
    protected $fillable = [
        'productId',
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


