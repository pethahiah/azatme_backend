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

    
    public function NqrMerchantRegistration(Request $request)
    {
      $testUrl = env('PayThru_Base_Test_Url');
      //return $testUrl;
      $token = $this->paythruService->handle();
      $endpoint = $testUrl.'/Nqr/Agg/Merchant/Register';
      //return $endpoint;
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

public function createSubMerchant(Request $request)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Sub';
  //return $endpoint;
  $token = $this->paythruService->handle();

$data = [
    "merchantNumber" => $request->merchantNumber,
    "name" => $request->name,
    "email" => $request->email,
    "phoneNumber" => $request->phoneNumber,
    "generateQrCodeWithFixedAmount" => true,
    "qrCodeAmount" => $request->qrCodeAmount,
    "storeId" => $request->storeId
];

$response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
    
])->post($endpoint, $data);
//return $response;
if($response->Successful())
{
$banks = json_decode($response->body(), true);
  return response()->json($banks);
}  

}

public function getSubMerchantUnderAllMerchant($id)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Subs';
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
  
])->get($endpoint."/$id");
return $response;
if($response->Successful())
{
$getSubMerchant = json_decode($response->body(), true);
return response()->json($getSubMerchant);
}

}


public function getSpecificSubMerchantUnderAMerchant($id)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Sub';
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
  
])->get($endpoint."/$id");
//return $response;
if($response->Successful())
{
$getSpecificSubMerchant = json_decode($response->body(), true);
return response()->json($getSpecificSubMerchant);
}

}


public function getSpecificSubMerchantInfo($merchantNumber)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Details';
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
  
])->get($endpoint."/$merchantNumber");
//return $response;
if($response->Successful())
{
$getSubMerchantInfo = json_decode($response->body(), true);
return response()->json($getSubMerchantInfo);
}

}

public function getMerchantTransactionReport(Request $request, $merchantNumber)
  {
      $testUrl = env('PayThru_Base_Test_Url');
      $token = $this->paythruService->handle();
      $endpoint = $testUrl.'/Nqr/agg/merchant/reports';

      //$pageNumber = 10;
      //return $pageNumber;

  $data = [
      "startTime" => $request->startTime,
      "endTime" => $request->endTime,
      "orderType" => $request->orderType,
      "page" => 2,
 
  ];

  $response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
  ])->post($endpoint."/$merchantNumber", $data);

  if($response->failed())
  {
    return false;
  }
    $getMerchantTransactionReport = json_decode($response->body(), true);
    return response()->json($getMerchantTransactionReport);
}

public function generateDynamicQrCode(Request $request, $merchantNumber)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $token = $this->paythruService->handle();
  $endpoint = $testUrl.'/Nqr/agg/merchant/transaction';

$data = [
  "channel" => $request->startTime,
  "subMchNo" => $request->endTime,
  "codeType" => $request->orderType,
  "amount" => $request->$amount,
  "order_no" => $request->orderType,
  "orderType" => $request->$amount,

];

$response = Http::withHeaders([
'Content-Type' => 'application/json',
'Authorization' => $token,
])->post($endpoint."/$merchantNumber", $data);

if($response->failed())
{
return false;
}
$ngrGenerateDynamicCode = json_decode($response->body(), true);
return response()->json($ngrGenerateDynamicCode);
}


public function merchantTransactionStatus(Request $request)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $token = $this->paythruService->handle();
  $endpoint = $testUrl.'/Nqr/agg/merchant/transaction/status';

$data = [
  "orderNo" => $request->orderNo,
  "merchantNumber" => $request->merchantNumber,
  "orderSn" => $request->orderSn
];

$response = Http::withHeaders([
'Content-Type' => 'application/json',
'Authorization' => $token,
])->post($endpoint."/$merchantNumber", $data);

if($response->failed())
{
return false;
}
$ngrGenerateDynamicCode = json_decode($response->body(), true);
return response()->json($ngrGenerateDynamicCode);
}



}


