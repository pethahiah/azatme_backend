<?php

namespace App\Services;

use App\Ajo;
use App\Bank;
use App\DirectDebitMandate;
use App\DirectDebitMandateUpdate;
use App\DirectDebitProduct;
use App\Invitation;
use App\PaymentDate;
use App\Services\PaythruService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;


class DirectDebit
{

    public $paythruService;
    protected $apiKey;
    protected $apiUrl;



    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
        $this->apiKey = env('PayThru_ApplicationId');
        $this->apiUrl = env('Paythru_Direct_Debt_Test_Url');
    }


    public function addProduct($productName, $productDescription)
    {
        try {

            // Store data in DirectDebitProduct
            $product = new DirectDebitProduct();
            $product->productName = $productName;
            $product->productDescription = $productDescription;
            $product->isUserResponsibleForCharges = true;
            $product->classification = "FixedContract";
            $product->partialCollectionEnabled = false;
            $product->save();

            // Remove fields before serializing
            unset($product->updated_at);
            unset($product->created_at);
            unset($product->id);

            // Serialize product object into JSON
            $productData = json_encode($product);

            Log::info('Payload sent to gateway: ' . $productData);

            $apiKey = env("PayThru_ApplicationId");
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $apiKey,
            ])->post($apiUrl . '/Product/create', [
                'productName' => $productName,
                'productDescription' => $productDescription,
            ]);
            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['succeed']) {
                    $productId = $responseData['data']['productId'];
                    $product->productId = $productId;
                    $product->save();
                    Log::info('Response from paythru API: ' . $response->body());
                } else {
                    Log::error('Error response from paythru API: ' . $response->body());
                    throw new \Exception('Failed to create product. paythru returned an error: ' . $responseData['message']);
                }
            } else {
                Log::error('Error response from paythru: ' . $response->body());
                throw new \Exception('Failed to create product. paythru returned an error.');
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // If product was created before the exception, delete it
            if (isset($product) && $product->exists) {
                $product->delete();
            }

            // Re-throw the exception to be caught by the caller
            throw $e;
        }
        return $product;
    }

    public function createMandate($validatedData, $ajo)
    {
        $apiKey = env("PayThru_ApplicationId");
        $user = Auth::user();

        $getInvitation = Invitation::where('ajo_id', $ajo->id)->first();
        $getPaymentDate = PaymentDate::where('invitation_id', $getInvitation->id)
            ->whereDate('collection_date', now()->toDateString())->first();

        $acct = $validatedData['accountNumber'];

        $bank = Bank::where('account_number', $acct)->first();

        if (!$bank) {
            return response()->json(['message' => 'Beneficiary Bank account not found'], 404);
        }

        $beneficiaryReferenceId = $bank->referenceId;

        try {
            // Store data in DirectDebitMandate
            $mandate = new DirectDebitMandate();
            $mandate->productId = $validatedData['productId'];
            $mandate->productName = $validatedData['productName'];
            $mandate->remarks = $validatedData['remarks'];
            $mandate->paymentAmount = $ajo->amount_per_member;
            $mandate->serviceReference = time() . $validatedData['productId'];
            $mandate->accountNumber = $validatedData['accountNumber'];
            $mandate->bankCode = $validatedData['bankCode'];
            $mandate->accountName = $validatedData['accountName'];
            $mandate->phoneNumber = $this->formatPhoneNumber($user->phone);
            $mandate->homeAddress = $validatedData['homeAddress'];
            $mandate->fileName = $validatedData['fileName'];
            $mandate->description = $validatedData['description'];
            $mandate->fileExtension = $validatedData['fileExtension'];
            $mandate->email = $user->email;
            $mandate->startDate = $validatedData['startDate'];
            $mandate->endDate = $validatedData['endDate'];
            $mandate->paymentFrequency = $validatedData['paymentFrequency'];
            $mandate->referenceCode = Str::random(10);
            $mandate->collectionAccountNumber = $beneficiaryReferenceId;
            $mandate->mandateType = "Instance";
            $mandate->routingOption = "Default";
            $mandate->save();

            // Format date fields
            $startDate = Carbon::parse($validatedData['startDate'])->toIso8601String();
            $endDate = Carbon::parse($validatedData['endDate'])->toIso8601String();

            // Construct the payload
            $payload = [
                'productId' => (string)$validatedData['productId'],
                'productName' => (string)$validatedData['productName'],
                'remarks' => (string)$validatedData['remarks'],
                'paymentAmount' => (string)$ajo->amount_per_member,
                'serviceReference' => (string)(time() . $validatedData['productId']),
                'accountNumber' => (string)$validatedData['accountNumber'],
                'bankCode' => (string)$validatedData['bankCode'],
                'accountName' => (string)$validatedData['accountName'],
                'phoneNumber' => (string)$this->formatPhoneNumber($user->phone),
                'homeAddress' => (string)$validatedData['homeAddress'],
                'fileName' => (string)$validatedData['fileName'],
                'description' => (string)$validatedData['description'],
                'fileExtension' => (string)$validatedData['fileExtension'],
                'emailAddress' => (string)$user->email,
                'startDate' => (string)$startDate,
                'endDate' => (string)$endDate,
                'paymentFrequency' => (string)$validatedData['paymentFrequency'],
                'referenceCode' => (string)Str::random(10),
                'collectionAccountNumber' => (string)$beneficiaryReferenceId,
                'mandateType' => "Instant",
                'routingOption' => "Default",
            ];
            Log::info('Payload sent to gateway: ' . json_encode($payload));

            // Send product data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $apiKey,
            ])->post($apiUrl . '/DirectDebit/mandate/create', $payload);

            // Log the response from the API
            Log::info('Response from paythru API: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                // Decode JSON response
                $responseData = $response->json();

                // Check if the API response indicates success
                if ($responseData['succeed']) {
                    $mandateId = $responseData['data']['mandateId'];
                    $mandate->mandateId = $mandateId;
                    $mandate->save();
                    // Log the response
                    Log::info('Mandate created successfully: ' . $response->body());
                } else {
                    // Log the error response
                    Log::error('Error response from paythru API: ' . $response->body());

                    // Throw an exception with the error message from the API response
                    throw new Exception('Failed to create mandate. paythru returned an error: ' . $responseData['message']);
                }
            } else {
                // Log the error response
                Log::error('Error response from paythru: ' . $response->body());

                // Throw an exception if the API call was not successful
                throw new Exception('Failed to create mandate. paythru returned an error.');
            }
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // If mandate was created before the exception, delete it
            if ($mandate && $mandate->exists) {
                $mandate->delete();
            }
            // Re-throw the exception to be caught by the caller
            throw $e;
        }

        return $mandate;
    }

    private function formatPhoneNumber($phone)
    {
        // Check if the phone number starts with '+234' and replace it with '234'
        if (strpos($phone, '+234') === 0) {
            return '234' . substr($phone, 4);
        }

        // Check if the phone number starts with '0' and replace it with '234'
        if (strpos($phone, '0') === 0) {
            return '234' . substr($phone, 1);
        }

        // Return the phone number as is if no formatting is needed
        return $phone;
    }

    public function updateMandate($requestData): DirectDebitMandate
    {
        // Retrieve existing mandate or create a new one if not found
        $mandate = DirectDebitMandate::findOrFail($requestData['mandateId'] ?? null);

        try {
            // Update mandate based on request type
            switch ($requestData['requestType']) {
                case 'Suspend':
                    $mandate->requestType = 'Suspended';
                    break;
                case 'Enable':
                    $mandate->requestType = 'Enable';
                    break;
                case 'Update':
                    $mandate->amountLimit = $requestData['amountLimit'] ?? $mandate->amountLimit;
                    break;
                default:
                    throw new Exception('Invalid request type');
            }
            // Save the updated mandate in DirectDebitMandateUpdate table
            $mandateUpdate = new DirectDebitMandateUpdate();
            $mandateUpdate->mandate_id = $mandate->id;
            $mandateUpdate->requestType = $mandate->requestType;
            $mandateUpdate->amount_limit = $mandate->amountLimit;
            $mandateUpdate->save();

            // Prepare payload for external API
            $payload = [
                'mandateId' => $mandate->id,
                'requestType' => $requestData['requestType'],
                'amountLimit' => $mandate->amountLimit,
            ];

            // Send updated mandate data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $paythruToken = $this->getPaythruToken();
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $paythruToken,
            ])->post($apiUrl.'/DirectDebit/mandate/update', $payload);

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

    public function getProductList()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->get($this->apiUrl . '/Product/list');

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error response from paythru: ' . $response->body());
                return ['error' => 'Failed to retrieve products.'];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching products from paythru: ' . $e->getMessage());
            return ['error' => 'An error occurred while fetching products.'];
        }
    }




}
