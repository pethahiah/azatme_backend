<?php

namespace App\Services;


use App\Business;
use App\BusinessTransaction;
use App\Customer;
use App\Product;
use Illuminate\Support\Facades\Auth;
use App\Services\PaythruService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MposService
{
    public $paythruService;


    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }


public function mposPay($request, $business_code)
{
    $prodUrl = env('PayThru_Base_Live_Url');
    $email = Auth::user()->email;

    $uniqueCodes = $request->input('unique_code', []);
    $quantities = $request->input('quantity', []);
    $quantities = is_array($quantities) ? $quantities : [$quantities];

    if (empty($uniqueCodes) || empty($quantities)) {
        return response()->json(['message' => 'Invalid input data'], 400);
    }

 $getBusinessVatOption = Business::where('owner_id', Auth::user()->id)
        ->where('business_code', $business_code)
        ->first();

    if (!$getBusinessVatOption) {
        return response()->json(['message' => 'Business not found'], 404);
    }

    $businessEmail = $getBusinessVatOption->business_email;
	$businessName = $getBusinessVatOption->business_name;
    if (count($uniqueCodes) === 1 && count($quantities) === 1) {
        return $this->processSinglePayment($uniqueCodes[0], $quantities[0], $getBusinessVatOption, $prodUrl, $business_code, $businessEmail,$businessName);
    } elseif (count($uniqueCodes) === count($quantities) && count($uniqueCodes) > 1) {
        return $this->processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl,$business_code, $businessEmail,$businessName);
    }

    return response()->json(['message' => 'Invalid input data'], 400);
}

private function processSinglePayment($uniqueCode, $quantity, $getBusinessVatOption, $prodUrl,$business_code, $businessEmail,$businessName)
{
    $vatRate = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
    $product = Product::where('unique_code', $uniqueCode)->first();

    if (!$product) {
        return response()->json(['message' => 'Product not found'], 404);
    }

    $quantity = is_numeric($quantity) ? $quantity : 0;
    $amount = is_numeric($product->amount) ? $product->amount : 0;
    $vatAmount = $amount * $quantity * $vatRate;
    $grandTotal = ($amount * $quantity) + $vatAmount;

    $data = $this->paymentData($grandTotal, $product, $prodUrl);
    $url = $prodUrl . '/transaction/create';
    $token = $this->paythruService->handle();

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
    ])->post($url, $data);

    $result = $this->handleResponse($response);

    if ($result['successful']) {
        $transaction = $this->saveTransaction($grandTotal, $vatAmount, $quantity,$business_code, $product, $result['payLink'], $businessEmail,$businessName);
        $responseData= [
            'data' => [
		'vat'=>$vatAmount,
		'quantity'=>$quantity,
                'business_email' => $businessEmail,
                'business_name' => $businessName,
                'transaction_amount' => $grandTotal,
                'created_at' => now()->toIso8601String(),
                'pay_link' => $result['payLink'],
            ],
            'exception' => null
        ];
		return $responseData;
    } else {
        return response()->json([
            'data' => null,
            'exception' => 'Error'
        ], 404);
    }
}

private function processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl, $business_code, $businessEmail,$businessName)
{
    $totalAmount = 0;
    $vatRate = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;

    foreach ($uniqueCodes as $index => $uniqueCode) {
        $quantity = $quantities[$index];
        $product = Product::where('unique_code', $uniqueCode)->first();

        if ($product) {
            $amount = is_numeric($product->amount) ? $product->amount : 0;
            $vatAmount = $amount * $quantity * $vatRate;
            $grandTotal = ($amount * $quantity) + $vatAmount;
            $totalAmount += $grandTotal;
        }
    }

    $token = $this->paythruService->handle();
    $data = $this->paymentData($totalAmount, null, $prodUrl);
    $url = $prodUrl . '/transaction/create';

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
    ])->post($url, $data);

    $result = $this->handleResponse($response);

    if ($result['successful']) {
        foreach ($uniqueCodes as $index => $uniqueCode) {
            $product = Product::where('unique_code', $uniqueCode)->first();
            if ($product) {
                $quantity = $quantities[$index];
                $amount = is_numeric($product->amount) ? $product->amount : 0;
                $vatAmount = $amount * $quantity * $vatRate;
                $grandTotal = ($amount * $quantity) + $vatAmount;
                $this->saveTransaction($grandTotal, $vatAmount, $quantity, $business_code, $product, $result['payLink'], $businessEmail,$businessName);
            }
        }
        $responseData= [
            'data' => [
                'business_email' => $businessEmail,
		'vat'=>$vatAmount,
                'quantity'=>$quantity,
                'business_name' => $businessName,
                'transaction_amount' => $totalAmount,
                'created_at' => now()->toIso8601String(),
                'pay_link' => $result['payLink'],
            ],
            'exception' => null
        ];
	return $responseData;
    } else {
        return response()->json([
            'data' => null,
            'exception' => 'Error.'
        ], 404);
    }
}

private function handleResponse($response)
{
    if ($response->failed()) {
        return ['successful' => false, 'message' => 'Transaction failed'];
    }

    $transaction = json_decode($response->body(), true);

    if (!$transaction['successful']) {
        return ['successful' => false, 'message' => 'Whoops! ' . $transaction['message']];
    }

    return $transaction;
}

private function paymentData($totalAmount, $product, $prodUrl): array
{
    $productId = env('PayThru_business_productid');
    $secret = env('PayThru_App_Secret');
    $hashSign = hash('sha512', $totalAmount . $secret);
    return [
        'amount' => $totalAmount,
        'productId' => $productId,
        'transactionReference' => time() . ($product ? $product->id : ''),
        'paymentDescription' => $product ? $product->description : 'Multiple products',
        'paymentType' => 1,
        'sign' => $hashSign,
        'displaySummary' => false,
    ];
}

private function saveTransaction($grandTotal, $vatAmount, $quantity, $business_code, $product, $payLink, $businessEmail,$businessName)
{
    $lastSegment = basename($payLink);

    return BusinessTransaction::create([
        'transaction_amount' => $grandTotal,
        'vat_amount' => $vatAmount,
        'qty' => $quantity,
        'created_at' => now(),
        'owner_id' => Auth::user()->id,
        'email' => $businessEmail,
        'business_code' => $business_code,
        'moto_id' => 1,
        'name' => 'MPOS',
        'product_id' => $product->id,
        'description' => $product->description,
        'paymentReference' => $lastSegment,
        'unique_code' => $product->unique_code,
        'product_code' => $this->generateUniqueCode()
    ]);
}

private function generateUniqueCode(): string
{
    return uniqid();
}


public function mPosOneTimePay($request, $business_code)
{
    $currentTimestamp = now();
    $prodUrl = env('PayThru_Base_Live_Url');
    $amount = $request->input('amount');
    $productId = env('PayThru_business_productid');
    $secret = env('PayThru_App_Secret');

    $hashSign = hash('sha512', $amount . $secret);
    $token = $this->paythruService->handle();
    $description = "Mpos payment option";

    $data = [
        'amount' => $amount,
        'productId' => $productId,
        'transactionReference' => time() . $amount,
        'paymentDescription' => $description,
        'paymentType' => 1,
        'sign' => $hashSign,
        'displaySummary' => false,
    ];

    $url = $prodUrl . '/transaction/create';

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
    ])->post($url, $data);

    if ($response->failed()) {
        return response()->json(['exception' => 'Transaction failed.'], 400);
    }

    $transaction = json_decode($response->body(), true);

    if (!$transaction['successful']) {
        return response()->json(['exception' => 'Whoops! ' . json_encode($transaction['message'])], 400);
    }

    $paylink = $transaction['payLink'];

    if ($paylink) {
        $lastSegment = basename($paylink);

        $info = BusinessTransaction::create([
            'transaction_amount' => $amount,
            'owner_id' => Auth::user()->id,
            'business_code' => $business_code,
            'email' => Auth::user()->email,
            'name' => 'MPOS',
            'product_id' => 1,
            'description' => $description,
            'paymentReference' => $lastSegment,
            'unique_code' => $this->generateUniqueCode()
        ]);

        // Retrieve business information
        $business = Business::where('business_code', $business_code)->first();

        if ($business) {
            $responseData = [
                'data' => [
                    'business_email' => $business->business_email,
                    'business_name' => $business->business_name,
                    'transaction_amount' => $amount,
                    'created_at' => $info->created_at->toIso8601String(),
                    'payLink' => $paylink,
                ],
                'exception' => null
            ];

            return $responseData;
        } else {
            return response()->json(['exception' => 'Business not found.'], 404);
        }
    }

    return response()->json(['exception' => 'Unexpected error occurred.'], 500);
}





    /**
     * Get all MPOS transactions per business and aggregate them by product_code.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $business_code
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllTransactionPerBusiness($request, $business_code)
    {
        $perPage = $request->input('per_page', 10);

        // Fetch aggregated residualAmount per product_code for the given business_code
        return BusinessTransaction::select('product_code', DB::raw('SUM(residualAmount) as total_Amount'))
            ->where('owner_id', Auth::id())
            ->where('business_code', $business_code)
            ->where('name', 'MPOS')
            ->groupBy('product_code')
            ->orderBy('total_Amount', 'desc')
            ->paginate($perPage);
    }




    public function getTransactionPerPaymentReference($request, $paymentReference)
    {
        // Validate the $business_code parameter to ensure it's there.
        if ($paymentReference){

            $getTransactionPerPaymentReference = BusinessTransaction::where('owner_id', Auth::id())
                ->where('paymentReference', $paymentReference)
                ->first();
            return $getTransactionPerPaymentReference;
        } else {
            return response()->json(['error' => 'payment reference not found'], 400);
        }
    }



}

