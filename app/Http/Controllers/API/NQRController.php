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
      $testUrl = env('PayThru_Base_Test_Url');
      $token = $this->paythruService->handle();
      $endpoint = $testUrl.'/Nqr/Agg/Merchant/Register';
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
          ])->post($endpoint, $data);
          if($response->successful())
          //return $response;
            {   
              $ngrRegistration = json_decode($response->body(), true);
              return response()->json($ngrRegistration);
            }
}   

public function merchantCollectionAccount(Request $request)
  {
      $testUrl = env('PayThru_Base_Test_Url');
      $token = $this->paythruService->handle();
      $endpoint = $testUrl.'/Nqr/agg/Merchant/Collections';

  $data = [
      "bankCode" => $request->bankCode,
      "accountName" => $request->accountName,
      "accountNumber" => $request->accountNumber,
      "merchantNumber" => $request->merchantNumber,
 
  ];

  $response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
  ])->post($endpoint, $data);

  if($response->failed())
  {
    return false;
  }
    $ngrCollectionAccount = json_decode($response->body(), true);
    return response()->json($ngrCollectionAccount);
}


public function getMerchantNumber($merchantNumber)
{
      $testUrl = env('PayThru_Base_Test_Url');
      $endpoint = $testUrl.'/Nqr/agg/Merchant/Collections';
      $token = $this->paythruService->handle();
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
        
  ])->get($endpoint."/$merchantNumber");
    //return $response;
    if($response->Successful())
    {
    $getMerchantAcoount = json_decode($response->body(), true);
      return response()->json($getMerchantAcoount);
    }  
}

public function createSubMerchant()
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Sub';
  $token = $this->paythruService->handle();

$data = [
    "merchantNumber" => $request->bankCode,
    "name" => $request->accountName,
    "email" => $request->accountNumber,
    "phoneNumber" => $request->merchantNumber,
    "generateQrCodeWithFixedAmount" => true,
    "qrCodeAmount" => $request->qrCodeAmount,
    "storeId" => $request->storeId
];

$response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
    
])->get($endpoint, $data);
//return $response;
if($response->Successful())
{
$banks = json_decode($response->body(), true);
  return response()->json($banks);
}  

}

public function getSubMerchantUnderAMercant($id)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Subs';
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
  
])->get($endpoint."/$id");
//return $response;
if($response->Successful())
{
$getSubMerchant = json_decode($response->body(), true);
return response()->json($getSubMerchant);
}

}






}