<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    //
    
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'business_code',
        'owner_id',
        'business_email',
        'business_address',
        'business_logo',
        'description',
        'type',
        'registration_number',
        'vat_id',
        'vat_option'
        
    ];
}
