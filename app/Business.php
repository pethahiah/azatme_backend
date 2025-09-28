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
        'vat_option',
	'uuid_code',
	'voucher_code',
        'state',
        'city',
        'region',
    ];

public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'business_vouchers', 'business_id', 'voucher_id')
            ->withPivot('voucher_code', 'created_at', 'updated_at');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
