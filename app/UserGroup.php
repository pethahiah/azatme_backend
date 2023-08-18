<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class UserGroup extends Model
{
    //

    use Softdeletes;

    protected $fillable = [
        'group_id',
        'reference_id',
        'user_id',
        'amount_payable',
        'amount_payed',
        'payed_date',
        'status',
        'split_method_id',
        'bankName',
        'bankCode',
        'account_number',
        'percentage',
        'percentage_per_user',
        'actualAmount',
        'email',
        'uique_code',
        'payThruReference',
        'fiName',
        'amount',
        'paymentReference',
        'paymentMethod',
        'commission',
        'residualAmount',
        'resultCode',
        'responseDescription',
	'uidd',
	'first_name',
	'last_name',
	'minus_residual',
	'has_updated_minus_residual',
	'stat',
	'name'
    ];


    public function expense()
    {
        return $this->belongsTo(Expense::class, 'group_id');
    }

    public function Usergroup()
    {
        return $this->hasOne(splittingMethod::class, 'split_method_id');
    }


}
