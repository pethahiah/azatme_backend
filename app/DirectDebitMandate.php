<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class DirectDebitMandate extends Model
{
    //
    protected $fillable = [
        'productId',
        'productName',
        'paymentAmount',
        'customer_phone',
        'serviceReference',
        'accountNumber',
        'bankCode',
        'accountName',
        'phoneNumber',
        'homeAddress',
        'fileName',
        'description',
        'fileBase64String',
        'fileExtension',
        'startDate',
        'endDate',
        'paymentFrequency',
        'packageId',
        'referenceCode',
        'collectionAccountNumber',
        'mandateType',
        'routingOption',
        ];

}
