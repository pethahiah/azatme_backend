<?php

namespace App\Services;


use App\Business;
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
        $current_timestamp = now();
        $prodUrl = env('PayThru_Base_Live_Url');

        $idCode = $this->generateUniqueCode();

        $uniqueCodes = $request->input('unique_code');
        $quantities = $request->input('quantity');

        if (!is_array($quantities)) {
            $quantities = [$quantities];
        }

        $email = $request->input('email');
        $user = Customer::where('customer_email', $email)->first();

        $cusName = $user->customer_name;
        $vat = 0;

        $getBusinessVatOption = Business::where('owner_id', Auth::user()->id)
            ->where('business_code', $business_code)
            ->first();
        $busName = $getBusinessVatOption->business_name;

        if (count($uniqueCodes) === 1 && count($quantities) === 1) {
            return $this->processSinglePayment($uniqueCodes[0], $quantities[0], $getBusinessVatOption, $prodUrl);
        } elseif (count($uniqueCodes) === count($quantities) && count($uniqueCodes) > 1) {
            return $this->processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl);
        }

        return response()->json(['message' => 'Invalid input data'], 400);
    }

    private function processSinglePayment($uniqueCode, $quantity, $getBusinessVatOption, $prodUrl)
    {
        $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
        $product = Product::where('unique_code', $uniqueCode)->first();
        $quantity = is_numeric($quantity) ? $quantity : 0;
        $amount = is_numeric($product->amount) ? $product->amount : 0;

        $vatAmount = $amount * $quantity * $vat;
        $grandTotal = ($amount * $quantity) + $vatAmount;
        $totalAmount = $grandTotal;

        $data = $this->paymentData($totalAmount, $product);
        $url = $prodUrl . '/transaction/create';
        $token = $this->paythruService->handle();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        return $this->handleResponse($response);
    }

    private function processMultiplePayments($uniqueCodes, $quantities, $getBusinessVatOption, $prodUrl): \Illuminate\Http\JsonResponse
    {
        $totalAmount = 0;
        $totalVatAmount = 0;
        $totalQuantity = 0;

        foreach ($uniqueCodes as $index => $uniqueCode) {
            $quantity = $quantities[$index];
            $product = Product::where('unique_code', $uniqueCode)->first();

            if ($product) {
                $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
                $amount = is_numeric($product->amount) ? $product->amount : 0;
                $vatAmount = $amount * $quantity * $vat;
                $grandTotal = ($amount * $quantity) + $vatAmount;

                $totalAmount += $grandTotal;
                $totalVatAmount += $vatAmount;
                $totalQuantity += $quantity;
            }

            $token = $this->paythruService->handle();
            $data = $this->paymentData($totalAmount, $product);
            $url = $prodUrl . '/transaction/create';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ])->post($url, $data);

            $result = $this->handleResponse($response);
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
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
            return response()->json(['message' => 'Whoops! ' . json_encode($transaction['message'])], 400);
        }

        return true;
    }

    private function paymentData($totalAmount, $product): array
    {
        // Create the payment data array based on your requirements
        return [
            'total_amount' => $totalAmount,
            'product' => $product,
        ];
    }

    private function generateUniqueCode(): string
    {
        // Generate a unique code for the transaction
        return uniqid();
    }
}
