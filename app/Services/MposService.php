<?php

namespace App\Services;


use App\Business;
use App\BusinessTransaction;
use App\Customer;
use App\Product;
use Illuminate\Support\Facades\Auth;
use App\Services\PaythruService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Log;


class MposService
{
    public $paythruService;


    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }


private function generateUniqueCode(): string
{
    return uniqid();
}




public function mposPay($request, $business_code)
{
    $prodUrl = env('PayThru_Base_Live_Url');
    $email = Auth::user()->email;

    $uniqueCodes = $request->input('unique_code', []);
    $quantities = $request->input('quantity', []);
    $quantities = is_array($quantities) ? $quantities : [$quantities];

    if (empty($uniqueCodes) || empty($quantities)) {
        return [
            'data' => null,
            'exception' => 'Invalid input data.'
        ];
    }

    $getBusinessVatOption = Business::where('owner_id', Auth::user()->id)
        ->where('business_code', $business_code)
        ->first();

    if (!$getBusinessVatOption) {
        return [
            'data' => null,
            'exception' => 'Business not found.'
        ];
    }

    $businessEmail = $getBusinessVatOption->business_email;

    if (count($uniqueCodes) === 1 && count($quantities) === 1) {
        return $this->processSinglePayment($uniqueCodes[0], $quantities[0], $getBusinessVatOption, $prodUrl, $business_code, $businessEmail);
    } elseif (count($uniqueCodes) === count($quantities) && count($uniqueCodes) > 1) {
        return $this->processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl, $business_code, $businessEmail);
    }

    return [
        'data' => null,
        'exception' => 'Invalid input data.'
    ];
}

private function processSinglePayment($uniqueCode, $quantity, $getBusinessVatOption, $prodUrl, $business_code, $businessEmail)
{
    $vatRate = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
    $product = Product::where('unique_code', $uniqueCode)->first();

    if (!$product) {
        return [
            'data' => null,
            'exception' => 'Product not found.'
        ];
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

    // Log the response to check the result
    Log::info('API Response: ' . print_r($result, true));

    if (isset($result['successful']) && $result['successful']) {
        $productCode = $this->generateUniqueCode();
        $payLink = $result['payLink'] ?? ''; // Ensure $payLink is defined
        
        Log::info('Result PayLink: ' . $payLink); 
        
        // Save the transaction
        $this->saveTransaction($grandTotal, $vatAmount, $quantity, $business_code, $product, $businessEmail, $productCode);

        // Update each transaction with the correct payLink
        if ($payLink) {
            BusinessTransaction::where('product_code', $productCode)
                ->where('owner_id', Auth::user()->id)
                ->update(['paymentReference' => basename($payLink)]);
        }

        return [
            'data' => [
                'business_email' => $businessEmail,
                'business_name' => $business_code,
                'transaction_amount' => $grandTotal,
                'created_at' => now()->toIso8601String(),
                'pay_link' => $payLink,
                'exception' => null
            ],
            'exception' => null
        ];
    } else {
        return [
            'data' => null,
            'exception' => 'Failed transaction.'
        ];
    }
}



private function processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl, $business_code, $businessEmail)
{
    $totalAmount = 0;
    $vatRate = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
    $productCode = $this->generateUniqueCode();  // Single product_code for all products

    foreach ($uniqueCodes as $index => $uniqueCode) {
        $quantity = $quantities[$index];
        $product = Product::where('unique_code', $uniqueCode)->first();

        if ($product) {
            $amount = is_numeric($product->amount) ? $product->amount : 0;
            $vatAmount = $amount * $quantity * $vatRate;
            $grandTotal = ($amount * $quantity) + $vatAmount;
            $totalAmount += $grandTotal;

            // Save each transaction separately without the payLink initially
            $this->saveTransaction($grandTotal, $vatAmount, $quantity, $business_code, $product, $businessEmail, $productCode);
        }
    }

    // Process payment for the total amount after processing individual transactions
    $data = $this->paymentData($totalAmount, null, $prodUrl);
    $url = $prodUrl . '/transaction/create';
    $token = $this->paythruService->handle();

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
    ])->post($url, $data);

    $result = $this->handleResponse($response);

    // Log the API response for debugging
    Log::info('API Response: ' . print_r($result, true));

    if (isset($result['successful']) && $result['successful']) {
        // Update transactions with the final payLink
        $payLink = $result['payLink'];
        Log::info('Final PayLink: ' . $payLink);

        // Update each transaction with the correct payLink
        BusinessTransaction::where('product_code', $productCode)
            ->where('owner_id', Auth::user()->id)
            ->update(['paymentReference' => basename($payLink)]);

        return [
            'data' => [
                'business_email' => $businessEmail,
                'business_name' => $business_code,
                'transaction_amount' => $totalAmount,
                'created_at' => now()->toIso8601String(),
                'pay_link' => $payLink,
                'exception' => null
            ],
            'exception' => null
        ];
    } else {
        return [
            'data' => null,
            'exception' => 'Failed transaction.'
        ];
    }
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

private function handleResponse($response)
{
    if ($response->failed()) {
        return [
            'successful' => false,
            'message' => 'Transaction failed'
        ];
    }

    $transaction = json_decode($response->body(), true);

    // Log the transaction response
    Log::info('Transaction Response: ' . print_r($transaction, true));

    if (!$transaction['successful']) {
        return [
            'successful' => false,
            'message' => 'Whoops! ' . $transaction['message']
        ];
    }

    return $transaction;
}


private function saveTransaction($grandTotal, $vatAmount, $quantity, $business_code, $product, $businessEmail, $productCode)
{
    // Log the details before saving
    return BusinessTransaction::create([
        'transaction_amount' => $grandTotal,
        'Grand_total' => $grandTotal,
        'vat' => $vatAmount,
        'qty' => $quantity,
        'owner_id' => Auth::user()->id,
        'email' => $businessEmail,
        'business_code' => $business_code,
        'moto_id' => 1,
        'name' => 'MPOS',
        'product_id' => $product->id,
        'description' => $product->description,
        'unique_code' => $product->unique_code,
        'product_code' => $productCode
    ]);
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

