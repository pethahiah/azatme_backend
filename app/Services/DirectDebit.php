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


// public function createMandate($validatedData, $ajo)
// {
//     $inviteLink = $validatedData['inviteLink'];
//     Log::info('Payload sent to gateway: ' . json_encode($inviteLink));

//     // Parse the invite link and extract necessary parameters
//     if (strpos($inviteLink, 'action=accept') !== false) {
//     $query = parse_url($inviteLink, PHP_URL_QUERY);
//     parse_str($query, $params);

//     $inviterToken = $params['inviter_token'];
//     $email = $params['email'];

//     Log::info('Inviter token: ' . $inviterToken);
//     Log::info('Email: ' . $email);

//     // Retrieve the invitation by token and email
//     $invitation = Invitation::where('token', $inviterToken)
//                             ->where('email', $email)
//                             ->first();

//     if (!$invitation) {
//         Log::warning('No invitation found for email: ' . $email . ' and token: ' . $inviterToken);
//         return response()->json(['message' => 'Invitation not found'], 404);
//     }

//     Log::info('Invitation found for email: ' . $email);

//     // Update invitation status
//     $invitation->status = 'accept';
//     if ($invitation->save()) {
//         Log::info('Invitation status updated to accept.');
//     } else {
//         Log::error('Failed to update invitation status.');
//     }
// }
//     // Prepare account details
//     $accountDetails = [
//         'accountNumber' => $validatedData['accountNumber'],
//         'bankName' => $validatedData['bankName'],
//         'accountName' => $validatedData['accountName'],
//         'bankCode' => $validatedData['bankCode'],
//         'referenceId' => $validatedData['referenceId']
//     ];

//     // Update or create a bank record
//     $bank = Bank::updateOrCreate(
//         ['account_number' => $accountDetails['accountNumber']],
//         [
//             'bank_name' => $accountDetails['bankName'],
//             'account_name' => $accountDetails['accountName'],
//             'bankCode' => $accountDetails['bankCode'],
//             'referenceId' => $accountDetails['referenceId'],
//             'DirectDebitreferenceId' => $accountDetails['referenceId']
//         ]
//     );

//     if (!$bank) {
//         return response()->json(['message' => 'Beneficiary Bank account not found'], 404);
//     }

//     // Set up user, beneficiary reference, and date handling
//     $user = Auth::user();
//     $beneficiaryReferenceId = $bank->referenceId;
//     $startDate = Carbon::parse($ajo->starting_date)->subDays(2)->format('Y-m-d\TH:i:s\Z');
//     $endDate = Carbon::parse($ajo->starting_date)->addYears(2)->format('Y-m-d\TH:i:s\Z');
//        


//     // Create DirectDebitMandate
//     $mandate = DirectDebitMandate::create([
//         'productId' => $validatedData['productId'],
//         'productName' => $validatedData['productName'],
//         'remarks' => $validatedData['remarks'],
//         'paymentAmount' => $ajo->amount_per_member,
//         'serviceReference' => time() . $validatedData['productId'],
//         'accountNumber' => $accountDetails['accountNumber'],
//         'bankCode' => $accountDetails['bankCode'],
//         'accountName' => $accountDetails['accountName'],
//         'phoneNumber' => $this->formatPhoneNumber($user->phone),
//         'homeAddress' => $validatedData['homeAddress'],
//         'description' => $validatedData['description'],
//         'user_id' => $user->id,
//         'email' => $user->email,
//         'startDate' => $startDate,
//         'endDate' => $endDate,
//         'paymentFrequency' => $validatedData['paymentFrequency'],
//         'referenceCode' => Str::random(10),
//         'collectionAccountNumber' => $beneficiaryReferenceId,
//         'mandateType' => "Instant",
//         'ajo_id' => $ajo->id,
//         'routingOption' => "Default"
//     ]);

//     // Construct and send the payload to the third-party API
//     $payload = [
//         'productId' => (string)$validatedData['productId'],
//         'productName' => (string)$validatedData['productName'],
//         'remarks' => (string)$validatedData['remarks'],
//         'paymentAmount' => (string)$ajo->amount_per_member,
//         'serviceReference' => (string)(time() . $validatedData['productId']),
//         'accountNumber' => (string)$accountDetails['accountNumber'],
//         'bankCode' => (string)$accountDetails['bankCode'],
//         'accountName' => (string)$accountDetails['accountName'],
//         'phoneNumber' => (string)$this->formatPhoneNumber($user->phone),
//         'homeAddress' => (string)$validatedData['homeAddress'],
//         'description' => (string)$validatedData['description'],
//         'emailAddress' => (string)$user->email,
//         'startDate' => (string)$startDate,
//         'endDate' => (string)$endDate,
//         'paymentFrequency' => (string)$validatedData['paymentFrequency'],
//         'referenceCode' => (string)Str::random(10),
//         'collectionAccountNumber' => (string)$beneficiaryReferenceId,
//         'mandateType' => "Instant",
//         'routingOption' => "Default"
//     ];

//     Log::info('Payload sent to gateway: ' . json_encode($payload));

//     try {
//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'ApplicationId' => $this->apiKey,
//         ])->post($this->apiUrl . '/DirectDebit/mandate/create', $payload);

//         Log::info('Response from paythru API: ' . $response->body());

//         if ($response->successful()) {
//             $responseData = $response->json();
//             if ($responseData['succeed']) {
//                 $mandate->update(['mandateId' => $responseData['data']['mandateId']]);
//                 Log::info('Mandate created successfully: ' . $response->body());
//             } else {
//                 Log::error('Error from paythru API: ' . $responseData['message']);
//                 throw new Exception('Failed to create mandate: ' . $responseData['message']);
//             }
//         } else {
//             Log::error('Error response from paythru: ' . $response->body());
//             throw new Exception('Failed to create mandate.');
//         }
//     } catch (Exception $e) {
//         Log::error('Error sending data to paythru: ' . $e->getMessage());
//         if ($mandate && $mandate->exists) {
//             $mandate->delete();
//         }
//         throw $e;
//     }

//     return [
//         'responseCode' => $response->json()['responseCode'],
//         'message' => $response->json()['message'] ?? 'Mandate created successfully',
//         'mandate' => $mandate
//     ];
// }


public function createMandate($validatedData, $ajo)
{
    $user = Auth::user();
    
    // Check for existing mandate
    $existingMandate = DirectDebitMandate::where('ajo_id', $ajo->id)
        ->where('user_id', $user->id)
        ->where('status', 1)
        ->first();
        
    if ($existingMandate) {
        return [
            'message' => 'The user has already created a mandate for this AJO transaction.',
            'code' => 409 
        ];
    }

    $inviteLink = $validatedData['inviteLink'];
    Log::info('Payload sent to gateway: ' . json_encode($inviteLink));

    // Check for acceptance of invitation
    if (strpos($inviteLink, 'action=accept') !== false) {
        $query = parse_url($inviteLink, PHP_URL_QUERY);
        parse_str($query, $params);

        $inviterToken = $params['inviter_token'] ?? null;
        $email = $params['email'] ?? null;

        Log::info('Inviter token: ' . $inviterToken);
        Log::info('Email: ' . $email);

        // Retrieve the invitation by token and email
        $invitation = Invitation::where('token', $inviterToken)
                                ->where('email', $email)
                                ->first();

        if (!$invitation) {
            Log::warning('No invitation found for email: ' . $email . ' and token: ' . $inviterToken);
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        Log::info('Invitation found for email: ' . $email);
    }

    // Prepare account details
    $accountDetails = [
        'accountNumber' => $validatedData['accountNumber'],
        'bankName' => $validatedData['bankName'],
        'accountName' => $validatedData['accountName'],
        'bankCode' => $validatedData['bankCode'],
        'referenceId' => $validatedData['referenceId']
    ];

    // Update or create a bank record
    $bank = Bank::updateOrCreate(
        ['account_number' => $accountDetails['accountNumber']],
        [
            'bank_name' => $accountDetails['bankName'],
            'account_name' => $accountDetails['accountName'],
            'bankCode' => $accountDetails['bankCode'],
            'referenceId' => $accountDetails['referenceId'],
            'DirectDebitreferenceId' => $accountDetails['referenceId']
        ]
    );

    if (!$bank) {
        return response()->json(['message' => 'Beneficiary Bank account not found'], 404);
    }

    // Set up user, beneficiary reference, and date handling
    $beneficiaryReferenceId = $bank->referenceId;
    $startDate = Carbon::parse($ajo->starting_date)->subDays(2)->format('Y-m-d\TH:i:s\Z');
    $endDate = Carbon::parse($ajo->starting_date)->addYears(2)->format('Y-m-d\TH:i:s\Z');

    // Create DirectDebitMandate
    $mandate = DirectDebitMandate::create([
        'productId' => $validatedData['productId'],
        'productName' => $validatedData['productName'],
        'remarks' => $validatedData['remarks'],
        'paymentAmount' => $ajo->amount_per_member,
        'serviceReference' => time() . $validatedData['productId'],
        'accountNumber' => $accountDetails['accountNumber'],
        'bankCode' => $accountDetails['bankCode'],
        'accountName' => $accountDetails['accountName'],
        'phoneNumber' => $this->formatPhoneNumber($user->phone),
        'homeAddress' => $validatedData['homeAddress'],
        'description' => $validatedData['description'],
        'user_id' => $user->id,
        'email' => $user->email,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'paymentFrequency' => $validatedData['paymentFrequency'],
        'referenceCode' => Str::random(10),
        'collectionAccountNumber' => $beneficiaryReferenceId,
        'mandateType' => "Instant",
        'ajo_id' => $ajo->id,
        'routingOption' => "Default"
    ]);

    // Construct and send the payload to the third-party API
    $payload = [
        'productId' => (string)$validatedData['productId'],
        'productName' => (string)$validatedData['productName'],
        'remarks' => (string)$validatedData['remarks'],
        'paymentAmount' => (string)$ajo->amount_per_member,
        'serviceReference' => (string)(time() . $validatedData['productId']),
        'accountNumber' => (string)$accountDetails['accountNumber'],
        'bankCode' => (string)$accountDetails['bankCode'],
        'accountName' => (string)$accountDetails['accountName'],
        'phoneNumber' => (string)$this->formatPhoneNumber($user->phone),
        'homeAddress' => (string)$validatedData['homeAddress'],
        'description' => (string)$validatedData['description'],
        'emailAddress' => (string)$user->email,
        'startDate' => (string)$startDate,
        'endDate' => (string)$endDate,
        'paymentFrequency' => (string)$validatedData['paymentFrequency'],
        'referenceCode' => (string)Str::random(10),
        'collectionAccountNumber' => (string)$beneficiaryReferenceId,
        'mandateType' => "Instant",
        'routingOption' => "Default"
    ];
    

    Log::info('Payloadddd sent to gateway: ' . json_encode($payload));
    Log::info(' I got here');


    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->post($this->apiUrl . '/DirectDebit/mandate/create', $payload);
Log::info(' I stoopped here');
        Log::info('Response from PayThru API: ' . $response->body());

        if ($response->successful()) {
            $responseData = $response->json();
            if ($responseData['succeed']) {
                $mandate->update(['mandateId' => $responseData['data']['mandateId']]);
                Log::info('Mandate created successfully: ' . $response->body());

                // Now update invitation status after mandateId is saved
                $invitation->status = 'accept';
                if ($invitation->save()) {
                    Log::info('Invitation status updated to accept.');
                } else {
                    Log::error('Failed to update invitation status.');
                }
            } else {
                Log::error('Error from PayThru API: ' . $responseData['message']);
                throw new Exception('Failed to create mandate: ' . $responseData['message']);
            }
        } else {
            Log::error('Error response from PayThru: ' . $response->body());
            throw new Exception('Failed to create mandate.');
        }
    } catch (Exception $e) {
        Log::error('Error sending data to PayThru: ' . $e->getMessage());
        if ($mandate && $mandate->exists) {
            $mandate->delete();
        }
        throw $e;
    }

    return [
        'responseCode' => $response->json()['responseCode'],
        'message' => $response->json()['message'] ?? 'Mandate created successfully',
        'mandate' => $mandate
    ];
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
            $mandateUpdate->user_id = Auth::user()->id;
            $mandateUpdate->save();

            // Prepare payload for external API
            $payload = [
                'mandateId' => $mandate->id,
                'requestType' => $requestData['requestType'],
                'amountLimit' => $mandate->amountLimit,
            ];

            // Send updated mandate data to third-party API

            $paythruToken = $this->getPaythruToken();
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $paythruToken,
            ])->post($this->apiUrl.'/DirectDebit/mandate/update', $payload);

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
    try {


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->get($this->apiUrl . '/payout/banks/list');

        // Log the response for debugging
        Log::debug('getBankList response', ['response' => $response->body()]);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true);
            
            // Check if the 'bankLists' array is empty
            if (empty($responseData['bankLists'])) {
                return response()->json(['message' => 'No bank lists available'], 200);
            }

            return response()->json($responseData['bankLists']);
        }

        // Log the unsuccessful response status
        Log::error('Failed to retrieve bank list', ['status' => $response->status(), 'body' => $response->body()]);
        return response()->json(['error' => 'Failed to retrieve bank list'], $response->status());
    } catch (\Exception $e) {
        // Log any exceptions
        Log::error('Exception occurred while retrieving bank list', ['exception' => $e->getMessage()]);
        return response()->json(['error' => 'An error occurred while retrieving bank list'], 500);
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

public function getProductList(): array
{
    try {
        // Fetching product list from API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->get($this->apiUrl . '/Product/list');

        // Logging the API response before any processing
        Log::info('API Response:', ['response' => $response->json()]);

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



    public function listMandates(array $validatedData): array
    {
        
         $userId = Auth::id();

        $check = DirectDebitProduct::where('user_id', $userId)->first();

        if (!$check || $check->productId != $validatedData['productId']) {
        return [
            'status' => false,
            'message' => 'Product does not belong to the authorized user.',
            'code' => 400
        ];
    }
        
        $payload = [
            'startDate' => $validatedData['startDate'],
            'endDate' => $validatedData['endDate'],
            'status' => $validatedData['status'],
            'productId' => $validatedData['productId'],  
            'bankCode' => $validatedData['bankCode'],
            'page' => $validatedData['page'],
            'pageSize' => $validatedData['pageSize'],
        ];

        // Log the payload for debugging
        Log::info('Payload sent to gateway: ' . json_encode($payload));

        try {
            // Send the payload to the external gateway

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl. '/mandate/list', $payload);

            // Log the response from the API
            Log::info('Response from paythru API: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            } else {
                // Log the error response
                Log::error('Error response from paythru: ' . $response->body());

                // Return the error response
                return [
                    'status' => false,
                    'message' => 'Failed to retrieve mandates.',
                    'code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // Return the exception message
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving mandates.',
                'code' => 500
            ];
        }
    }


    public function getMandateDetails(array $validatedData): array
    {

        $payload = [
            'mandateId' => $validatedData['mandateId'], 
            'referenceCode' => $validatedData['referenceCode'],
            'page' => $validatedData['page'],
            'pageSize' => $validatedData['pageSize'],
        ];

        // Log the payload for debugging
        Log::info('Payload sent to gateway for mandate details: ' . json_encode($payload));

        try {
            // Send the payload to the external gateway

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl. '/mandate/details', $payload);

            // Log the response from the API
            Log::info('Response from paythru API for mandate details: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            } else {
                // Log the error response
                Log::error('Error response from paythru for mandate details: ' . $response->body());

                // Return the error response
                return [
                    'status' => false,
                    'message' => 'Failed to retrieve mandate details.',
                    'code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru for mandate details: ' . $e->getMessage());

            // Return the exception message
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving mandate details.',
                'code' => 500
            ];
        }
    }

    public function getMandateTransactions(array $validatedData): array
    {
        $payload = [
            'mandateId' => $validatedData['mandateId'],  
            'startRange' => $validatedData['startRange'],
            'endRange' => $validatedData['endRange'],
            'productId' => $validatedData['productId'],    
            'bankCode' => $validatedData['bankCode'],
            'page' => $validatedData['page'],
            'pageSize' => $validatedData['pageSize'],
            'showSuccessOnly' => $validatedData['showSuccessOnly'],
        ];

        // Log the payload for debugging
        Log::info('Payload sent to gateway for mandate transactions: ' . json_encode($payload));

        try {
            // Send the payload to the external gateway
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl. '/mandate/transactions', $payload);

            // Log the response from the API
            Log::info('Response from paythru API for mandate transactions: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            } else {
                // Log the error response
                Log::error('Error response from paythru for mandate transactions: ' . $response->body());

                // Return the error response
                return [
                    'status' => false,
                    'message' => 'Failed to retrieve mandate transactions.',
                    'code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru for mandate transactions: ' . $e->getMessage());

            // Return the exception message
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving mandate transactions.',
                'code' => 500
            ];
        }
    }
    
    

    public function getMandateSettlements(array $validatedData)
    {
        $payload = [
            'mandateId' => $validatedData['mandateId'], 
            'startRange' => $validatedData['startRange'],
            'endRange' => $validatedData['endRange'],
            'productId' => $validatedData['productId'],  
            'bankCode' => $validatedData['bankCode'],
            'page' => $validatedData['page'],
            'pageSize' => $validatedData['pageSize'],
            'showSuccessOnly' => $validatedData['showSuccessOnly'],
        ];

        // Log the payload for debugging
        Log::info('Payload sent to gateway for mandate settlements: ' . json_encode($payload));

        try {
            // Send the payload to the external gateway
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl. 'mandate/settlements', $payload);

            // Log the response from the API
            Log::info('Response from paythru API for mandate settlements: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            } else {
                // Log the error response
                Log::error('Error response from paythru for mandate settlements: ' . $response->body());
                // Return the error response
                return [
                    'status' => false,
                    'message' => 'Failed to retrieve mandate settlements.',
                    'code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru for mandate settlements: ' . $e->getMessage());

            // Return the exception message
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving mandate settlements.',
                'code' => 500
            ];
        }
    }

 public function getMandateStatus($mandateId, $ajoId): array
{
    Log::info('Retrieving status for mandate ID: ' . $mandateId);

    // Check if the mandate belongs to the authorized user
    $mandate = DirectDebitMandate::where('ajo_id', $ajoId)->first();

    if (!$mandate || $mandate->mandateId !== $mandateId) {
        return [
            'status' => false,
            'message' => 'Mandate does not belong to the authorized user.',
            'code' => 400,
        ];
    }

    try {
        // Send the request to the external gateway
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->get("{$this->apiUrl}/DirectDebit/mandate/{$mandateId}/status");

        // Log the response from the API
        Log::info('Response from API for mandate status: ' . $response->body());

        // Check if the API call was successful
        if ($response->successful()) {
            $responseData = $response->json();

            // Debugging log to see the response data
            Log::info('Response Data: ', $responseData);

            // Update the mandate status if response code is "00"
            if ($responseData['responseCode'] === "00" && $mandateId == $mandate->mandateId) {
                Log::info('Updating mandate status to true for mandate ID: ' . $mandate->id);


                $mandate->status = 1;

                // Attempt to save the mandate
                if ($mandate->save()) {
                    Log::info('Mandate status updated successfully for mandate ID: ' . $mandate->id);
                } else {
                    Log::error('Failed to save mandate status for mandate ID: ' . $mandate->id);
                }
            } else {
                Log::info('Response code was not "00" or mandate ID did not match.');
            }

            return [
                'status' => true,
                'data' => $responseData,
            ];
        } else {
            // Log the error response
            Log::error('Error response from API for mandate status: ' . $response->body());

            return [
                'status' => false,
                'message' => 'Failed to retrieve mandate status.',
                'code' => $response->status(),
            ];
        }
    } catch (\Exception $e) {
        // Log the exception
        Log::error('Error retrieving mandate status: ' . $e->getMessage());

        return [
            'status' => false,
            'message' => 'An error occurred while retrieving mandate status.',
            'code' => 500,
        ];
    }
}




   public function getMandateSchedules(array $validatedData): array
{
    $userId = Auth::id();

    // Retrieve the mandate schedule for the authenticated user
    $checkSchedule = DirectDebitMandate::where('user_id', $userId)->first();

    // Check if the retrieved mandate matches the provided data
    if (!$checkSchedule || $checkSchedule->mandateId != $validatedData['mandateId'] || $checkSchedule->referenceCode != $validatedData['referenceCode']) {
        return [
            'status' => false,
            'message' => 'Mandate does not belong to the authorized user.',
            'code' => 400
        ];
    }

    $payload = [
        'mandateId' => $validatedData['mandateId'],
        'referenceCode' => $validatedData['referenceCode'],
        'page' => $validatedData['page'],
        'pageSize' => $validatedData['pageSize'],
    ];

    // Log the payload for debugging
    Log::info('Payload sent to gateway for mandate schedules: ' . json_encode($payload));

    try {
        // Send the payload to the external gateway
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->post($this->apiUrl . '/mandate/schedules', $payload);

        // Log the response from the API
        Log::info('Response from API for mandate schedules: ' . $response->body());

        // Check if API call was successful
        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()
            ];
        } else {
            // Log the error response
            Log::error('Error response from API for mandate schedules: ' . $response->body());

            // Return the error response
            return [
                'status' => false,
                'message' => 'Failed to retrieve mandate schedules.',
                'code' => $response->status()
            ];
        }
    } catch (\Exception $e) {
        // Log the exception
        Log::error('Error retrieving mandate schedules: ' . $e->getMessage());

        // Return the exception message
        return [
            'status' => false,
            'message' => 'An error occurred while retrieving mandate schedules.',
            'code' => 500
        ];
    }
}


    public function initiateDirectDebitRequest(array $validatedData): array
    {
        $payload = [
            'recipient' => $validatedData['recipient'],
            'expiryDate' => $validatedData['expiryDate'],
            'startDate' => $validatedData['startDate'],
            'endDate' => $validatedData['endDate'],
            'amountLimit' => $validatedData['amountLimit'],
            'billingCycle' => $validatedData['billingCycle'],
            'description' => $validatedData['description'],
            'productId' => $validatedData['productId'],
            'serviceReference' => $validatedData['serviceReference'],
            'packageId' => $validatedData['packageId'],
            'bankCode' => $validatedData['bankCode'],
            'mandateAccountName' => $validatedData['mandateAccountName'],
            'phoneNumber' => $validatedData['phoneNumber'],
            'mandateAccountNumber' => $validatedData['mandateAccountNumber'],
            'payerAddress' => $validatedData['payerAddress'],
            'creditAccountNumber' => $validatedData['creditAccountNumber'],
            'creditAccountName' => $validatedData['creditAccountName'],
            'preferredCompletionOptions' => $validatedData['preferredCompletionOptions'],
            'payerName' => $validatedData['payerName']
        ];

        // Log the payload for debugging
        try {
            // Send the payload to the external gateway
            $apiUrl = env("Paythru_Direct_Debit_Test_Url") . '/api/v1/DirectDebit/mandate/request/initiate';
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl. '/mandate/request/initiate', $payload);

            // Log the response from the API
            Log::info('Response from paythru API for initiating direct debit request: ' . $response->body());

            // Check if API call was successful
            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            } else {
                // Log the error response
                Log::error('Error response from paythru for initiating direct debit request: ' . $response->body());

                // Return the error response
                return [
                    'status' => false,
                    'message' => 'Failed to initiate direct debit request.',
                    'code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru for initiating direct debit request: ' . $e->getMessage());

            // Return the exception message
            return [
                'status' => false,
                'message' => 'An error occurred while initiating direct debit request.',
                'code' => 500
            ];
        }
    }
    
     public function getUserProducts(int $perPage = 10)
    {
        $userId = Auth::id(); 
        return DirectDebitProduct::where('user_id', $userId)
                      ->orderBy('created_at', 'desc')
                      ->paginate($perPage); 
    }
    
    public function getAllMandatesPerUser(int $perPage = 10)
    {
        $userId = Auth::id();
        return DirectDebitMandateUpdate::where('user_id', $userId)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

}
