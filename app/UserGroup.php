<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\SoftDeletes;
use illuminate\Database\Eloquent\Factories\HasFactory;

class UserGroup extends Model
{
    //

    use HasFactory, Softdeletes;

    protected $fillable = [
        'group_id',
        'reference_id',
        'user_id',
        'amount_payable',
        'amount_payed',
        'payed_date',
        'status',
        'split_method_id'

        
    ];


    public function Uxergroup()
    {
        return $this->belongsTo(Expense::class, 'group_id');
    }

    public function Usergroup()
    {
        return $this->hasOne(splittingMethod::class, 'split_method_id');
    }


}
