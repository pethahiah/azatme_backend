<?php

namespace App\Services;

use App\Ajo;
use App\DirectDebitMandate;
use App\DirectDebitProduct;
use App\Invitation;
use App\PaymentDate;
use App\Services\PaythruService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class DirectDebitService
{

    public $paythruService;

    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }


    public function addProduct(array $requestData): DirectDebitProduct
    {
        // Initialize product variable
        $product = null;

        try {
            // Store data in DirectDebitProduct
            $product = new DirectDebitProduct();
            $product->productName = $requestData['productName'];
            $product->isPacketBased = false;
            $product->productDescription = $requestData['productDescription'];
            $product->isUserResponsibleForCharges = true;
            $product->classification = "FixedContract";
            $product->partialCollectionEnabled = false;
            $product->save();

            // Serialize product object into JSON
            $productData = json_encode($product);

            // Send product data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $paythruToken = $this->getPaythruToken();
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $paythruToken,
            ])->post($apiUrl.'/Product/create', ['product' => $productData]);
            // Check if API call was successful
            if ($response->successful()) {
                // Decode JSON response
                $responseData = $response->json();

                // Check if the API response indicates success
                if ($responseData['succeed']) {
                    // Retrieve product ID from API response
                    $productId = $responseData['data']['productId'];

                    // Update product with API-provided product ID
                    $product->productId = $productId;
                    $product->save();

                    // Log the response
                    Log::info('Response from paythru API: ' . $response->body());
                } else {
                    // Log the error response
                    Log::error('Error response from paythru API: ' . $response->body());

                    // Throw an exception with the error message from the API response
                    throw new Exception('Failed to create product. paythru returned an error: ' . $responseData['message']);
                }
            } else {
                // Log the error response
                Log::error('Error response from paythru: ' . $response->body());

                // Throw an exception if the API call was not successful
                throw new Exception('Failed to create product. paythru returned an error.');
            }
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // If product was created before the exception, delete it
            if ($product && $product->exists) {
                $product->delete();
            }
            // Re-throw the exception to be caught by the caller
            throw $e;
        }
        return $product;
    }



    public function createMandate($requestData, $ajo): DirectDebitMandate
    {
        $apiKey = env("PayThru_ApplicationId");
       // $user = Auth::user()->email;
        $getInvitation = Invitation::where('ajo_id', $ajo->id)->first();
        $getPaymentDate = PaymentDate::where('invitation_id', $getInvitation->id)
            ->whereDate('collection_date', now()->toDateString())->first();
        // Initialize mandate variable
        $mandate = null;

        try {
            // Store data in DirectDebitProduct
            $mandate = new DirectDebitMandate();
            $mandate->productId = $requestData['productId'];
            $mandate->productName = $requestData['productName'];
            $mandate->remarks = $requestData['remarks'];
            $mandate->paymentAmount = $ajo->amount_per_member;
            $mandate->customer_phone = $requestData['customer_phone'];
            $mandate->serviceReference = $apiKey;
            $mandate->accountNumber= $requestData['accountNumber'];
            $mandate->bankCode = $requestData['bankCode'];
            $mandate->accountName = $requestData['accountName'];
            $mandate->phoneNumber = $requestData['phoneNumber'];
            $mandate->homeAddress = $requestData['homeAddress'];
            $mandate->fileName = $requestData['fileName'];
            $mandate->description= $requestData['description'];
            $mandate->fileBase64String = $requestData['fileBase64String'];
            $mandate->fileExtension = $requestData['fileExtension'];
            $mandate->startDate = $ajo->starting_date;
            $mandate->endDate = $requestData['endDate'];
            $mandate->paymentFrequency= $requestData['paymentFrequency'];
            $mandate->packageId = $requestData['packageId'];
            $mandate->referenceCode = Str::random(10);
            $mandate->collectionAccountNumber = $requestData['collectionAccountNumber'];
            $mandate->mandateType = "Regular";
            $mandate->routingOption = "Default";
            $mandate->save();

            // Serialize product object into JSON
            $mandateData = json_encode($mandate);

            // Send product data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $paythruToken = $this->getPaythruToken();
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $paythruToken,
            ])->post($apiUrl.'/DirectDebit/mandate/create', ['mandate' => $mandateData]);

            // Check if API call was successful
            if ($response->successful()) {
                // Decode JSON response
                $responseData = $response->json();

                // Check if the API response indicates success
                if ($responseData['succeed']) {

                    // Log the response
                    Log::info('Response from paythru API: ' . $response->body());
                } else {
                    // Log the error response
                    Log::error('Error response from paythru API: ' . $response->body());

                    // Throw an exception with the error message from the API response
                    throw new Exception('Failed to create product. paythru returned an error: ' . $responseData['message']);
                }
            } else {
                // Log the error response
                Log::error('Error response from paythru: ' . $response->body());

                // Throw an exception if the API call was not successful
                throw new Exception('Failed to create product. paythru returned an error.');
            }
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // If product was created before the exception, delete it
            if ($mandate && $mandate->exists) {
                $mandate->delete();
            }
            // Re-throw the exception to be caught by the caller
            throw $e;
        }
        return $mandate;
    }

    public function updateMandate($requestData): DirectDebitMandate
    {
        // Retrieve existing mandate or create a new one if not found
        $mandate = DirectDebitMandate::findOrFail($requestData['mandateId'] ?? null);

        try {
            // Update mandate based on request type
            switch ($requestData['requestType']) {
                case 'Suspend':
                    $mandate->status = 'Suspended';
                    break;
                case 'Enable':
                    $mandate->status = 'Active';
                    break;
                case 'Update':
                    $mandate->amountLimit = $requestData['amountLimit'] ?? $mandate->amountLimit;
                    break;
                default:
                    throw new Exception('Invalid request type');
            }



            // Save updated mandate
            $mandate->save();

            // Send updated mandate data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $paythruToken = $this->getPaythruToken();
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $paythruToken,
            ])->post($apiUrl.'/DirectDebit/mandate/update', ['mandate' => $mandate]);

            // Check if API call was successful
            if ($response->successful()) {
                // Decode JSON response
                $responseData = $response->json();

                // Check if the API response indicates success
                if ($responseData['succeed']) {
                    // Log the response
                    Log::info('Response from external API: ' . $response->body());
                } else {
                    // Log the error response
                    Log::error('Error response from external API: ' . $response->body());

                    // Throw an exception with the error message from the API response
                    throw new Exception('Failed to update mandate. External API returned an error: ' . $responseData['message']);
                }
            } else {
                // Log the error response
                Log::error('Error response from external API: ' . $response->body());

                // Throw an exception if the API call was not successful
                throw new Exception('Failed to update mandate. External API returned an error.');
            }
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error updating mandate: ' . $e->getMessage());

            // Re-throw the exception to be caught by the caller
            throw $e;
        }

        return $mandate;
    }


    public function getBankList()
    {
        $TestUrl = env('Paythru_Direct_Debt_Test_Url');
        $paythruToken = $this->getPaythruToken();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $paythruToken,
        ])->get($TestUrl.'/payout/banks/list');
        //return $response;
        if($response->Successful())
        {
            $banks = json_decode($response->body(), true);
            return response()->json($banks);
        }
    }


    private function getPaythruToken()
    {
        $token = $this->paythruService->handle();

        if (!$token) {
            return "Token retrieval failed";
        } elseif (is_string($token) && strpos($token, '403') !== false) {
            return response()->json([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ], 403);
        }
        return $token;
    }
}
