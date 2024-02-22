<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //

    protected $fillable = [
        //
        
        'name',
        'description',
        'unique_code',
        'category_id',
        'subcategory_id',
        'amount',
        'user_id',
        'business_id',
        'business_code',
	'quantity'
        ];
}
