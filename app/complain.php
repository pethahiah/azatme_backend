<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class complain extends Model
{
    //

    protected $fillable = [
        'name',
        'expense_name',
        'user_id',
        'description',
        'severity'
        
    ];

    public function user()
    {
        return $this->hasMany(User::class, 'user_id');
    }
}
