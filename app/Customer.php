<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;


class Customer extends Model
{
    use Notifiable;

    protected $fillable = [
        'customer_name',
        'customer_code',
        'customer_email',
        'customer_phone',
        'owner_id'
        
    ];

    public function routeNotificationForMail($notification)
    {
        // Return email address only...
        return $this->customer_email;
 
    }
}
