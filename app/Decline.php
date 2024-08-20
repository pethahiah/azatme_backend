<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Decline extends Model
{
    //
	protected $fillable = [
        'reason',
        'remark',
        'invitation_id',
        'inviter_id',
        'invitee_name'
    ];

    public function Decline()
    {
        return $this->belongsTo(Invitation::class, 'invitation_id');
    }
}
