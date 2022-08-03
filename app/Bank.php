<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    //
use SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'account_number'
    ];



public function User()
    {
return $this->hasMany(ExpenseSubCategory::class, 'user_id');
    }

}
