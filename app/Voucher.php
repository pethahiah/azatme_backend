<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use Softdeletes;


    protected $fillable = [
        'voucher_code', 'sponsor_id',  'user_id', 'purpose',
        'expiry_date', 'limit', 'type', 'code_generation_method', 'location', 'voucher_amount', 'amount_per_code', 'status', 'claimed_by', 'claimed_at', 'state'
    ];


    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

public function businesses()
{
    return $this->belongsToMany(Business::class, 'business_voucher', 'voucher_id', 'business_id')
        ->withPivot('voucher_code', 'created_at', 'updated_at');
}


}


