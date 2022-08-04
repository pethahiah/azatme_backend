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
        'subcategory_id',
        'amount',
        'description'
        
    ];

    public function user()
    {
        return $this->hasMany(Expense::class, 'user_id');
    }

    


}
