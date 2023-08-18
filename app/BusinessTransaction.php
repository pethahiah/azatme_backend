<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessTransaction extends Model
{
    //

    protected $fillable = [
        //
        
                'owner_id',
                'name',
                'unique_code',
                'email',
                'amount',
                'description',
                'moto_id',
                'bankName',
                'bankCode',
                'account_number',
                'Grand_total',
                'issue_date',
                'product_id',
                'due_days',
                'due_date',
                'status',
                'total',
                'vat',
                'invoice_number',
                'qty',
                'rate',
                'business_code',
                'transaction_amount',
                'payThruReference',
                'fiName',
                'status',
                'amount',
                'paymentReference',
                'merchantReference',
                'paymentMethod',
                'commission',
                'residualAmount',
                'resultCode',
                'responseDescription',
		'minus_residual',
		'stat'
        ];
}
