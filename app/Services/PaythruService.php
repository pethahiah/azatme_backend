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
        

        return $token;
    }
}


public function handlesssss()
{
    // Optional: Use this when testing with a pre-generated token
    $testToken = 'Paythru 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'; // truncated

    // Use this token directly
    return $testToken;
}




}

