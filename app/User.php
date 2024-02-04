<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
     use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'otp', 'phone','first_name', 'middle_name', 'last_name', 'address', 'state', 'country', 'city', 
'image', 'usertype', 'registration_code', 'company_name','nimc','bvn', 'isVerified','enrollment_username','nin_bvnDetails','accessToken','face_image','lga_of_origin', 'age', 'gender'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function userExpense()
    {
        return $this->belongsTo(userExpense::class, 'principal_id','id')->withDefault();
    }
    
    
     public function userGroup()
    {
        return $this->belongsTo(userGroup::class, 'reference_id','id')->withDefault();
    }


    public function Expense()
    {
        return $this->hasMany(Expense::class, 'user_id','id')->withDefault();
    }
}
