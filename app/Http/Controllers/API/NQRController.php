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
    /**
     * @group NQR
     *
     * API endpoints for nqr management.
     * Register a new merchant.
     *
     * @bodyParam name string required The name of the merchant. Example: John Doe Ltd.
     * @bodyParam tin string required The TIN of the merchant. Example: 123456789
     * @bodyParam contact string required The contact person for the merchant. Example: John Doe
     * @bodyParam phone string required The phone number of the merchant. Example: 08012345678
     * @bodyParam email string required The email address of the merchant. Example: john.doe@example.com
     * @bodyParam address string required The address of the merchant. Example: 123 Merchant St.
     * @bodyParam bankNo string required The bank number. Example: 12345
     * @bodyParam accountName string required The account name. Example: John Doe Ltd.
     * @bodyParam accountNumber string required The account number. Example: 1234567890
     * @bodyParam remarks string Optional Additional remarks. Example: Registered for NQR.
     *
     * @response 200 {
     *   "merchantNumber": "123456",
     *   "qrCode": "https://example.com/qr/123456"
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     * @response 500 {
     *   "message": "Invalid response from API."
     * }
     *
     * @post /nqr-merchant-registration
     */
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


    /**
     * Get all merchants for the authenticated user.
     *@group NQR
     * @response 200 [
     *   {
     *     "merchantNumber": "123456",
     *     "name": "John Doe Ltd.",
     *     "contact": "John Doe",
     *     "phone": "08012345678"
     *   }
     * ]
     *
     * @get /get-all-merchants
     */

public function getAllMerchant()
{
 	$getMerchant = nrqMerchant::where('auth_id', Auth::user()->id)->get();
	return response()->json($getMerchant);

}

    /**
     * Create a merchant collection account.
     *@group NQR
     * @bodyParam bankCode string required The bank code. Example: 012
     * @bodyParam accountName string required The name on the account. Example: John Doe Ltd.
     * @bodyParam accountNumber string required The account number. Example: 1234567890
     * @bodyParam merchantNumber string required The merchant number. Example: 123456
     *
     * @response 200 {
     *   "status": "success",
     *   "details": "Collection account created successfully."
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /create-merchant-collection-account
     */
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

    /**
     * Get merchant details by merchant number.
     *@group NQR
     * @urlParam merchantNumber string required The merchant number to retrieve details for. Example: 123456
     *
     * @response 200 {
     *   "merchantNumber": "123456",
     *   "name": "John Doe Ltd.",
     *   "contact": "John Doe",
     *   "phone": "08012345678",
     *   "email": "john.doe@example.com",
     *   "address": "123 Merchant St."
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @get /get-merchant-number/{merchantNumber}
     */

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

    /**
     * Create a sub-merchant.
     *@group NQR
     * @bodyParam merchantNumber string required The merchant number to associate with the sub-merchant. Example: 123456
     * @bodyParam name string required The name of the sub-merchant. Example: Jane Doe Ltd.
     * @bodyParam email string required The email address of the sub-merchant. Example: jane.doe@example.com
     * @bodyParam phoneNumber string required The phone number of the sub-merchant. Example: 08098765432
     * @bodyParam qrCodeAmount float required Amount associated with the QR code. Example: 500.00
     * @bodyParam storeId string required The store ID for the sub-merchant. Example: store123
     *
     * @response 200 {
     *   "status": "success",
     *   "subMerchantDetails": {
     *     "subMerchantNumber": "654321",
     *     "qrCode": "https://example.com/qr/654321"
     *   }
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /create-sub-merchant
     */

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

    /**
     * Get all sub-merchants under a specific merchant.
     *@group NQR
     * @urlParam id int required The ID of the merchant. Example: 1
     *
     * @response 200 [
     *   {
     *     "subMerchantNumber": "654321",
     *     "name": "Jane Doe Ltd.",
     *     "email": "jane.doe@example.com",
     *     "phoneNumber": "08098765432"
     *   }
     * ]
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @get /get-all-submerchant-under-merchant/{id}
     */

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


    /**
     * Get specific sub-merchant under a merchant.
     *@group NQR
     * @urlParam id int required The ID of the sub-merchant. Example: 1
     *
     * @response 200 {
     *   "subMerchantNumber": "654321",
     *   "name": "Jane Doe Ltd.",
     *   "email": "jane.doe@example.com",
     *   "phoneNumber": "08098765432"
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /get-specific-submerchnat-under-merchant/{id}
     */

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

    /**
     * Get detailed information of a specific merchant.
     *@group NQR
     * @urlParam merchantNumber string required The merchant number to get details for. Example: 123456
     *
     * @response 200 {
     *   "merchantNumber": "123456",
     *   "name": "John Doe Ltd.",
     *   "contact": "John Doe",
     *   "phone": "08012345678",
     *   "email": "john.doe@example.com",
     *   "address": "123 Merchant St.",
     *   "details": "Detailed information about the merchant."
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @get /get-specific-merchant-info/{merchantNumber}
     */

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

    /**
     * Get merchant transaction report.
     *@group NQR
     * @bodyParam startTime string required Start time for the report. Example: 2024-01-01T00:00:00Z
     * @bodyParam endTime string required End time for the report. Example: 2024-01-31T23:59:59Z
     * @bodyParam orderType string required The type of order. Example: purchase
     * @bodyParam page int Optional Page number for paginated results. Example: 1
     *
     * @response 200 {
     *   "report": [
     *     {
     *       "transactionId": "1234567890",
     *       "amount": 100.00,
     *       "status": "completed"
     *     }
     *   ]
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /get-merchant-trans-report/{merchantNumber}
     */

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


    /**
     * Generate a dynamic QR code.
     *@group NQR
     * @bodyParam channel string required The channel for the QR code. Example: web
     * @bodyParam subMchNo string required The sub-merchant number. Example: 654321
     * @bodyParam codeType string required The type of code. Example: payment
     * @bodyParam amount float required The amount associated with the QR code. Example: 500.00
     * @bodyParam order_no string required The order number. Example: order12345
     * @bodyParam orderType string required The type of order. Example: purchase
     *
     * @response 200 {
     *   "qrCode": "https://example.com/qr/dynamic123456",
     *   "status": "success"
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /generate-dynamic-qrcode/{merchantNumber}
     */

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

    /**
     * Get the status of a merchant transaction.
     *@group NQR
     * @bodyParam orderNo string required The order number for the transaction. Example: order12345
     * @bodyParam merchantNumber string required The merchant number for the transaction. Example: 123456
     * @bodyParam orderSn string required The order serial number. Example: serial12345
     *
     * @response 200 {
     *   "status": "completed",
     *   "details": "Transaction details here."
     * }
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /get-merchant-transaction-status
     */

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


