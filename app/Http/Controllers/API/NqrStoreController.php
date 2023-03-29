<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\PaythruService;

class NqrStoreController extends Controller
{
    //

  public $paythruService;

  public function __construct(PaythruService $paythruService)
  {
      $this->paythruService = $paythruService;
  }


  public function storeGenerateDyanmicQrCode(Request $request)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $token = $this->paythruService->handle();
  $endpoint = $testUrl.'/Nqr/transaction/GenerateDynamicQrCode';

$data = [
  "channel" => $request->channel,
  "subMchNo" => $request->subMchNo,
  "codeType" => $request->codeType,
  "amount" => $request->amount,
  "order_no" => $request->order_no,
  "orderType" => $request->orderType
];

$response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => $token,
])->post($endpoint."/$merchantNumber", $data);

if($response->failed())
{
return false;
}
$storeGenerateDyanmicQrCode = json_decode($response->body(), true);
return response()->json($storeGenerateDyanmicQrCode);
}

public function getStoreTransactionReport(Request $request)
  {
      $testUrl = env('PayThru_Base_Test_Url');
      $token = $this->paythruService->handle();
      $endpoint = $testUrl.'/Nqr/transaction/reports';

  $data = [
      "startTime" => $request->startTime,
      "endTime" => $request->endTime,
      "orderType" => $request->orderType,
      "page" => $request->$pageNumber,
 
  ];

  $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
  ])->post($endpoint, $data);

  if($response->failed())
  {
    return false;
  }
    $getStoreTransactionReport = json_decode($response->body(), true);
    return response()->json($getStoreTransactionReport);
}


public function storeTransactionStatus(Request $request)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $token = $this->paythruService->handle();
  $endpoint = $testUrl.'/Nqr/transaction/TransactionStatus';

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
$storeTransactionStatus = json_decode($response->body(), true);
return response()->json($storeTransactionStatus);
}


public function getStore()
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/manage/stores';
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
])->get($endpoint);

if($response->Successful())
{
$getStore = json_decode($response->body(), true);
return response()->json($getStore);
}

}

public function createStore()
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl.'/Nqr/manage/stores';
  $token = $this->paythruService->handle();

  $data = [
    "storeName" => $request->startTime,
    "location" => $request->endTime,
    "subMerchants" => $request->orderType,
    "manager" => $request->$pageNumber,
];

$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
])->get($endpoint);

if($response->Successful())
{
$createStore = json_decode($response->body(), true);
return response()->json($createStore);
}

}

public function getListSpecificSubMerchantInStore($id)
{
  $testUrl = env('PayThru_Base_Test_Url');
  $endpoint = $testUrl."/Nqr/manage/stores/$d/SubMerchants";
  $token = $this->paythruService->handle();
$response = Http::withHeaders([
  'Content-Type' => 'application/json',
  'Authorization' => $token,
  
])->get($endpoint);
//return $response;
if($response->Successful())
{
$getListSpecificSubMerchantInStore = json_decode($response->body(), true);
return response()->json($getListSpecificSubMerchantInStore);
}

}


}
