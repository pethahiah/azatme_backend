<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ajo extends Model
{
    //
    protected $fillable = [
        'name',
        'uique_code',
        'user_id',
        'frequency',
        'member_count',
        'starting_date',
        'cycle',
        'description',
        'amount_per_member',
        'description'
        
        
    ];
}

