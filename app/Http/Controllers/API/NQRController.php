<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Nqr;
use App\Setting;    
use Carbon\Carbon;
use Auth;
use App\nrqMerchant;
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
        $testUrl = "https://services.paythru.ng";
        //return $testUrl;
        $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
        $endpoint = $testUrl.'/Nqr/Agg/Merchant/Register';
        $user = Auth::user();

        if ($user->usertype === 'merchant') {
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
                "referenceCode" => time().$user->id ,
                "remarks" => $request->remarks,
            ];

            // dd($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ])->post($endpoint, $data);

            if ($response->successful()) {
                //return $response;
                $ngrRegistration = json_decode($response->body(), true);

                if (is_array($ngrRegistration) && isset($ngrRegistration['merchantNumber'])) {
                    $nqrSync = nrqMerchant::create([
                        "auth_id" => Auth::user()->id,
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
                        "merchantNumber" => $ngrRegistration['merchantNumber'],
			"qrcode" => $ngrRegistration['qrCode'],
                    ]);
                    return response()->json($ngrRegistration);
                } else {
                    // If 'merchantNumber' key is not found or not an array, handle the error
                    return response()->json('Invalid response from API.', 500);
                }
	}
        } else {
            return response()->json('You are not authorized to perform this action');
    }
}

public function getAllMerchant()
{
 	$getMerchant = nrqMerchant::where('auth_id', Auth::user()->id)->get();
	return response()->json($getMerchant);

}

public function merchantCollectionAccount(Request $request)
  {
      $testUrl = "https://services.paythru.ng";
      $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
      $testUrl = "https://services.paythru.ng";
      $endpoint = $testUrl.'/Nqr/agg/Merchant/Collections';
      $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
  $testUrl = "https://services.paythru.ng";
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Sub';
  //return $endpoint;
  $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
  $testUrl = "https://services.paythru.ng";
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Subs';
  $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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


public function getSpecificSubMerchantUnderAMerchant($id)
{
  $testUrl = "https://services.paythru.ng";
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Sub';
  $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
  $testUrl = "https://services.paythru.ng";
  $endpoint = $testUrl.'/Nqr/agg/Merchant/Details';
  $token = $this->paythruService->handle();
 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
      $testUrl = "https://services.paythru.ng";
      $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
  $testUrl = "https://services.paythru.ng";
  $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
  $testUrl = "https://services.paythru.ng";
  $token = $this->paythruService->handle();
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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


