<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    //
	protected $fillable = [
        'name',
	'complain_reference_code',
        'expense_name',
        'user_id',
        'description',
        'severity',
	'status'

    ];

    public function user()
    {
        return $this->hasMany(User::class, 'user_id');
    }
	
   public function comments()
    {
        return $this->hasMany(Comment::class);
    }


}
