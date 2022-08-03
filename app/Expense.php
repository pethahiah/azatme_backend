<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\SoftDeletes;
use illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    //
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'name',
        'uique_code',
        'user_id',
        'category_id',
        'subcategory_id',
        'amount',
        'description'
        
    ];

    public function user()
    {
        return $this->hasMany(Expense::class, 'user_id');
    }

    


}
