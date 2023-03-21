<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    //
    protected $fillable = [
        //
        'to',
        'email',
        'salutation',
        'facebookLink',
        'whatsappNumber',
        'finalgreetings',
        'campaignImage',
        'url',
        'subject',
        'customer_id'
        ];
}
