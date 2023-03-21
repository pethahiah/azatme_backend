<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    //
    
    protected $fillable = [
        'account_name',
        'account_number',
        'description',
        'expense_id',
        'beneficiary_id',
        'amount',
        'bank'
    ];
}
