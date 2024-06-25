<?php

namespace App\Services;


use App\Business;
use App\BusinessTransaction;
use App\Customer;
use App\Product;
use Illuminate\Support\Facades\Auth;
use App\Services\PaythruService;
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
        $email = $request->input('email');
        $user = Customer::where('customer_email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

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

        if (count($uniqueCodes) === 1 && count($quantities) === 1) {
            return $this->processSinglePayment($uniqueCodes[0], $quantities[0], $getBusinessVatOption, $prodUrl, $user, $business_code);
        } elseif (count($uniqueCodes) === count($quantities) && count($uniqueCodes) > 1) {
            return $this->processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl, $user, $business_code);
        }

        return response()->json(['message' => 'Invalid input data'], 400);
    }

    private function processSinglePayment($uniqueCode, $quantity, $getBusinessVatOption, $prodUrl, $user, $business_code)
    {
        $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
        $product = Product::where('unique_code', $uniqueCode)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $quantity = is_numeric($quantity) ? $quantity : 0;
        $amount = is_numeric($product->amount) ? $product->amount : 0;
        $vatAmount = $amount * $quantity * $vat;
        $grandTotal = ($amount * $quantity) + $vatAmount;
        $totalAmount = $grandTotal;

        $data = $this->paymentData($totalAmount, $product, $prodUrl);
        $url = $prodUrl . '/transaction/create';
        $token = $this->paythruService->handle();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        $result = $this->handleResponse($response);
        if ($result['successful']) {
            $this->saveTransaction($totalAmount, $user, $business_code, $product, $result['payLink']);
        }

        return $result;
    }

    private function processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl, $user, $business_code): \Illuminate\Http\JsonResponse
    {
        $totalAmount = 0;

        foreach ($uniqueCodes as $index => $uniqueCode) {
            $quantity = $quantities[$index];
            $product = Product::where('unique_code', $uniqueCode)->first();

            if ($product) {
                $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
                $amount = is_numeric($product->amount) ? $product->amount : 0;
                $vatAmount = $amount * $quantity * $vat;
                $grandTotal = ($amount * $quantity) + $vatAmount;

                $totalAmount += $grandTotal;

                $token = $this->paythruService->handle();
                $data = $this->paymentData($totalAmount, $product, $prodUrl);
                $url = $prodUrl . '/transaction/create';

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => $token,
                ])->post($url, $data);

                $result = $this->handleResponse($response);
                if (!$result['successful']) {
                    return response()->json(['message' => 'Payment processing failed'], 400);
                }

                $this->saveTransaction($totalAmount, $user, $business_code, $product, $result['payLink']);
            }
        }

        return response()->json(['message' => 'Payment processed successfully'], 200);
    }

    private function handleResponse($response)
    {
        if ($response->failed()) {
            return response()->json(['message' => 'Transaction failed'], 400);
        }

        $transaction = json_decode($response->body(), true);

        if (!$transaction['successful']) {
            return response()->json(['message' => 'Whoops! ' . $transaction['message']], 400);
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
            'transactionReference' => time() . $product->id,
            'paymentDescription' => $product->description,
            'paymentType' => 1,
            'sign' => $hashSign,
            'displaySummary' => false,
        ];
    }

    private function saveTransaction($totalAmount, $user, $business_code, $product, $payLink)
    {
        $lastSegment = basename($payLink);

        BusinessTransaction::create([
            'transaction_amount' => $totalAmount,
            'owner_id' => Auth::user()->id,
            'email' => $user->customer_email,
            'business_code' => $business_code,
            'moto_id' => 1,
            'name' => 'MPOS',
            'product_id' => $product->id,
            'description' => $product->description,
            'paymentReference' => $lastSegment,
            'unique_code' => $this->generateUniqueCode()
        ]);
    }
    private function generateUniqueCode(): string
    {
        // Generate a unique code for the transaction
        return uniqid();
    }

    public function mPosOneTimePay($request): \Illuminate\Http\JsonResponse
    {
        $currentTimestamp = now();
        $prodUrl = env('PayThru_Base_Live_Url');
        $amount = $request->input('amount');
        $productId = env('PayThru_business_productid');
        $timestamp = strtotime($currentTimestamp);
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
            return response()->json(['message' => 'Transaction failed.'], 400);
        }

        $transaction = json_decode($response->body(), true);

        if (!$transaction['successful']) {
            return response()->json(['message' => 'Whoops! ' . json_encode($transaction['message'])], 400);
        }

        $paylink = $transaction['payLink'];

        if ($paylink) {
            $lastSegment = basename($paylink);

            $info = BusinessTransaction::create([
                'transaction_amount' => $amount,
                'owner_id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'business_code' => 1,
                'moto_id' => 1,
                'name' => 'MPOS',
                'product_id' => 1,
                'description' => $description,
                'paymentReference' => $lastSegment,
                'unique_code' => $this->generateUniqueCode()
            ]);

            return response()->json($transaction);
        }

        return response()->json(['message' => 'Unexpected error occurred.'], 500);
    }



}
