<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Tokenn;




class PaythruService
{


public function handle()
{
    $maxRetry = 5;
    $current_timestamp = now();
    $timestamp = strtotime($current_timestamp);
    $secret = env('PayThru_App_Secret');
    $PayThru_AppId = env('PayThru_ApplicationId');
    $AuthUrl = env('Paythru_Auth_Url');

    // Check if a token exists and is not expired
//    $tokenRecord = Tokenn::first();
    
    
  //  if ($tokenRecord && $tokenRecord->expiration_date > now()) {
    //     return Crypt::decrypt($tokenRecord->token);
   // }

    $response = Http::retry($maxRetry, 100)->withHeaders([
        'Content-Type' => 'application/json',
        'Timestamp' => $timestamp,
    ])->post($AuthUrl, [
        'ApplicationId' => $PayThru_AppId,
        'password' => hash('sha256', $secret . $timestamp),
    ]);

    if ($response->successful()) {
        $access = $response->object()->data;
        $paythru = "Paythru";
        $token = $paythru . " " . $access;
        
        // Store the token and its expiration date in the database
     //   Tokenn::updateOrCreate([], [
       //     'token' => Crypt::encrypt($token),
         //   'expiration_date' => now()->addDays(5),
       // ]);

        return $token;
    }
}



}

