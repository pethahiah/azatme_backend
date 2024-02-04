<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    //
    use SoftDeletes;


    protected $fillable = [
        'name',
        'uique_code',
        'user_id',
        'category_id',
        'actual_amount',
        'subcategory_id',
        'amount',
        'description',
	'confirm'
        
    ];

     public function expense()
    {
        return $this->hasMany(userExpense::class, 'principal_id', 'user_id');
    }
    
    
     public function group()
    {
        return $this->hasMany(userGroup::class, 'reference_id', 'user_id');
    }


public function userExpenses()
    {
        return $this->hasMany(UserExpense::class, 'expenses_id', 'id');
    }



public function userGroups()
    {
        return $this->hasMany(UserExpense::class, 'expenses_id', 'id');
    }



}
