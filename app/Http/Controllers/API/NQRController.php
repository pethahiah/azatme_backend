<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Nqr;
use App\Setting;    
use Carbon\Carbon;
use App\Services\PaythruService;


class NQRController extends Controller
{

  public $paythruService;

  public function __construct(PaythruService $paythruService)
  {
      $this->paythruService = $paythruService;
  }

    //
    public function NqrMerchantRegistration(Request $request)
    {
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
     // echo $timestamp;
      $secret = env('PayThru_App_Secret');
      $hash = hash('sha256', $secret . $timestamp);
      $PayThru_AppId = env('PayThru_ApplicationId');
      $prodUrl = env('PayThru_Base_Live_Url');
      
      
      $token = $this->paythruService->handle();

        //return $token;

        $data = [
            "name" => $request->name,
            "tin" => $request->tin,
            "contact" => $request->contact,
            "phone" => $request->phone,
            "email" => $request->email,
            "address" => $request->address,
            "bankNo" => $request->bankNo,
            "accountName" => $request->accountName,
            "accountNumber" => $request->accountNumber,
            "referenceCode" => $request->referenceCode,
            "remarks" => $request->remarks,
        ];
        
   // dd($data);
          $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
          ])->post('http://sandbox.paythru.ng/Nqr/Agg/Merchant/Register', $data);
          if($response->successful())
          //return $response;
            {   
              $ngrRegistration = json_decode($response->body(), true);
              return response()->json($ngrRegistration);
            }

}   



  public function merchantCollectionAccount(Request $request)
  {
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
      $secret = env('PayThru_App_Secret');
      $hash = hash('sha256', $secret . $timestamp);
      $PayThru_AppId = env('PayThru_ApplicationId');
      $prodUrl = env('PayThru_Base_Live_Url');

      $token = $this->paythruService->handle();
  
  //return $token;
    //$url = $prodUrl;
    $urls = $prodUrl.'/Nqr/agg/Merchant/Collections';

    $data = [
      "bankCode" => $request->bankCode,
      "accountName" => $request->accountName,
      "accountNumber" => $request->accountNumber,
      "merchantNumber" => $request->merchantNumber,
 
  ];


     $response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
  ])->post($urls, $data);

  if($response->failed())
  {
    return false;
  }
    $ngrCollectionAccount = json_decode($response->body(), true);
    return response()->json($ngrCollectionAccount);
        
  
}


    public function getMerchantNumber($merchantNumber)

    {
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
     // echo $timestamp;
      $secret = env('PayThru_App_Secret');
      $hash = hash('sha256', $secret . $timestamp);
      $PayThru_AppId = env('PayThru_ApplicationId');
      $prodUrl = env('PayThru_Base_Live_Url');
      
      $token = $this->paythruService->handle();

      $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
        
  ])->get($prodUrl."Nqr/agg/Merchant/Collections/$merchantNumber");
    //return $response;
    if($response->Successful())
    {
      $banks = json_decode($response->body(), true);
      return response()->json($banks);
    }  

    

}
}