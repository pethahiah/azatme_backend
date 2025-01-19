<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'name',
        'user_id'
        
    ];



   }
