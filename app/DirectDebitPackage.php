<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DirectDebitPackage extends Model
{
    //
    protected $fillable = [
        'productId',
        'productName',
        'description',
    ];

}
