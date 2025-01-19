<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class Kyc extends Model
{
    //

//use HasFactory;

    protected $fillable = [
        'user_id',
        'identity_card',
        'utility_bill',
        'proof_of_address',
        'business_registration_certificate',
        'business_registration_number',
        'company_name',
        'share_capital',
	'status',
	'description',
    ];

/**
     * Get the user that owns the KYC profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
