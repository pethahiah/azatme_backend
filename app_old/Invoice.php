<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    //
    protected $fillable = [
        //
        'owner_id',
        'name',
        'unique_code',
        'email',
        'description',
        'moto_id',
        'Amount',
        'bankName',
        'bankCode',
        'account_number',
        'invoice_number',
        'qty',
        'vat',
        'due_days',
        'due_date',
        'issue_date',  
        'Grand_total',
    
        ];
}
