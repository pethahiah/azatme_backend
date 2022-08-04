<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class userExpense extends Model
{
    //

    use SoftDeletes;



    protected $fillable = [
        'expense_id',
        'principal_id',
        'user_id',
        'payable',
        'payed',
        'payed_date',
        'status',
        'split_method_id'

        
    ];

    public function UserExpense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function user()
    {
        return $this->hasMany(userExpense::class, 'principal_id', 'user_id');
    }

    public function Uxerexpense()
    {
        return $this->hasOne(splittingMethod::class, 'split_method_id');
    }
}
