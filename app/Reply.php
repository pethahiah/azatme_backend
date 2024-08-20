<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class Reply extends Model
{
    //
	use SoftDeletes;

protected $fillable = ['content', 'comment_id', 'user_id'];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

public function user()
    {
        return $this->belongsTo(User::class);
    }


}
