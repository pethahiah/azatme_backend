<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Comment extends Model
{
    //

	use SoftDeletes;


 protected $fillable = ['content', 'feedback_id', 'user_id'];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

	public function user()
    {
        return $this->belongsTo(User::class);
    }
}
