<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;


class PaythruService
{


 public function handle()
{
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
     // echo $timestamp; 
      $secret = env('PayThru_App_Secret');
      $hash = hash('sha256', $secret . $timestamp);
      $PayThru_AppId = env('PayThru_ApplicationId');
      $TestUrl = env('PayThru_Base_Test_Url');
      $AuthUrl = env('Paythru_Auth_Url');

//return $AuthUrl;

      $data = [
        'ApplicationId' => $PayThru_AppId,
        'password' => $hash
      ];
//return $data;
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Timestamp' => $timestamp,
  ])->post($AuthUrl, $data);
//    return $response;
    if($response->Successful())
    {
      $access = $response->object();
      $accesss = $access->data;
      $paythru = "Paythru";
      $token = $paythru." ".$accesss;

      return $token;
    }


}




   











}

