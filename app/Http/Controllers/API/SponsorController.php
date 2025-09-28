<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SponsorService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\VoucherPayment;
use App\SponsorWallet;
use App\SponsorLimit;
use Auth;
use Log;



class SponsorController extends Controller
{
    //
    protected $sponsorService;

    public function __construct(SponsorService $sponsorService)
    {
        $this->sponsorService = $sponsorService;
    }
    
    
      /**
     * Fetch all vouchers created by a sponsor.
     *
     * @queryParam per_page int optional Number of items per page. Example: 15
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Fetched vouchers successfully.",
     *     "data": {
     *         "data": [
     *             {
     *                 "id": 1,
     *                 "voucher_code": "ABC123",
     *                 "voucher_amount": 100.00,
     *                 "created_at": "2024-08-05T00:00:00Z",
     *                 "updated_at": "2024-08-05T00:00:00Z"
     *             }
     *         ],
     *         "current_page": 1,
     *         "last_page": 10,
     *         "per_page": 15,
     *         "total": 150
     *     }
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch vouchers.",
     *     "error": "Detailed error message"
     * }
     */


public function getBySponsor(Request $request): JsonResponse
{
    $user = Auth::user();

    // Ensure the authenticated user is of type 'sponsor'
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsors have access.', [], 403);
    }

    $perPage = $request->input('per_page', 15);
    $voucherId = $request->input('voucher_id', null);

    try {
        $vouchers = $this->voucherService->getBySponsor($user->id, $perPage, $voucherId);

        // Dump vouchers for debugging
        dd($vouchers);

        if ($voucherId && !$vouchers) {
            return ApiResponse::error('Voucher not found.', [], 404);
        }
        
        

        return ApiResponse::success('Fetched vouchers successfully.', $vouchers);
    } catch (\Exception $e) {
        Log::error('Error fetching vouchers', ['error' => $e->getMessage()]);
        return ApiResponse::error('Failed to fetch vouchers.', ['error' => $e->getMessage()], 500);
    }
}






public function getWalletBalance()
    {
	$userId = Auth::user()->id;
        $response = $this->sponsorService->getUserWalletBalance($userId);
        return response()->json($response);
    }

public function getUserPaymentsAndSponsorWallet(Request $request)
{
    try {
        // Fetch the user to ensure it exists
        $userId = Auth::User()->id;
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        // Get all payments for this specific user
        $payments = VoucherPayment::where('sponsor_id', $userId)->get();

        // Get the sponsor wallet balance for this user
        $sponsor = SponsorWallet::where('user_id', $userId)->first();

        if (!$sponsor) {
            return response()->json(['status' => 'error', 'message' => 'Sponsor not found'], 404);
        }

        // Prepare the response data for the user
        $response = [
            'user_id' => $userId,
            'wallet_balance' => $sponsor->wallet_balance,
            'payments' => $payments
        ];

        return response()->json(['status' => 'success', 'data' => $response], 200);
    } catch (\Exception $e) {
        Log::error("Error fetching payments and sponsor wallet balance for user: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['status' => 'error', 'message' => 'Unexpected error occurred'], 500);
    }
}


public function createSponsor(Request $request): JsonResponse
{
    // Ensure the authenticated user is authorized to create sponsor accounts
    $user = auth()->user();

    // Validate incoming request data
    $validated = $request->validate([
        'sponsor_name' => 'required|string|max:191',
        'sponsor_registration_number' => 'required|string|max:191',
        'sponsor_description' => 'nullable|string|max:191',
        'sponsor_registration_certificate' => 'nullable|file|max:191',
        'tin_number' => 'nullable|string|max:191',
        'type' => 'required|in:government,private',
    ]);

    // Include the authenticated user's ID and set isSponsorVerified to 1
    $validated['user_id'] = $user->id;
    $validated['isSponsorVerified'] = 1;

    // Create the sponsor account using the SponsorService
    $response = $this->sponsorService->createSponsor($validated);

    // Return the response
    return response()->json($response, $response['success'] ? 201 : 400);
}

 // Get all sponsor accounts created by a user
    public function getAllSponsors(Request $request): JsonResponse
    {
        // Ensure the authenticated user is authorized to view sponsor accounts
        $user = auth()->user();

        // Fetch all sponsors by user ID
        $sponsors = $this->sponsorService->getAllSponsorsByUserId($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Fetched all sponsor accounts successfully.',
            'data' => $sponsors
        ]);
    }



    // Get a single sponsor account by sponsor ID
    public function getSponsorById(Request $request, $sponsorId): JsonResponse
    {
        // Ensure the authenticated user is authorized to view sponsor accounts
        $user = auth()->user();

        // Fetch sponsor by ID and ensure it belongs to the authenticated user
        $sponsor = $this->sponsorService->getSponsorById($sponsorId);

        if (!$sponsor || $sponsor->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor not found or you do not have permission to access it.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fetched sponsor account successfully.',
            'data' => $sponsor
        ]);
    }


 // Update a sponsor account
public function updateSponsor(Request $request, $sponsorId): JsonResponse
{
    $user = auth()->user();

    // Validate the incoming request data
    $validated = $request->validate([
        'sponsor_name' => 'nullable|string|max:191',
        'sponsor_registration_number' => 'nullable|string|max:191',
        'sponsor_description' => 'nullable|string|max:191',
        'sponsor_registration_certificate' => 'nullable|string|max:191',
        'tin_number' => 'nullable|string|max:191',
        'type' => 'nullable|in:government,private',
    ]);

    try {
        // Ensure the sponsor belongs to the authenticated user
        $sponsor = $this->sponsorService->getSponsorById($sponsorId);

        if (!$sponsor || $sponsor->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor not found or you do not have permission to access it.'
            ], 404);
        }

        // Remove empty values from the validated array
        $validated = array_filter($validated, fn ($value) => !is_null($value) && $value !== '');

        // Force `isSponsorVerified` to be updated
        $validated['isSponsorVerified'] = 1;

        // Update sponsor information
        $updatedSponsor = $this->sponsorService->updateSponsor($sponsorId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Sponsor account updated successfully.',
            'data' => $updatedSponsor
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update sponsor account.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    // Delete a sponsor account
    public function deleteSponsor(Request $request, $sponsorId): JsonResponse
    {
        $user = auth()->user();

        try {
            // Ensure the sponsor belongs to the authenticated user
            $sponsor = $this->sponsorService->getSponsorById($sponsorId);

            if (!$sponsor || $sponsor->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sponsor not found or you do not have permission to access it.'
                ], 404);
            }

            // Delete the sponsor account
            $this->sponsorService->deleteSponsor($sponsorId);

            return response()->json([
                'success' => true,
                'message' => 'Sponsor account deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sponsor account.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Sponsors
     *
     * Get sponsor details.
     *
     * This endpoint retrieves details of a specific sponsor based on their ID.
     *
     * @urlParam sponsorId int required The ID of the sponsor. Example: 1
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Sponsor details fetched successfully.",
     *     "data": {
     *         "id": 1,
     *         "name": "Sponsor Name",
     *         "email": "sponsor@example.com",
     *         "phone": "+1234567890",
     *         "created_at": "2024-01-01T00:00:00.000000Z",
     *         "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     * }
     *
     * @response 404 {
     *     "success": false,
     *     "message": "Sponsor not found.",
     *     "error": "Detailed error message"
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch sponsor details.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/sponsors/1', [
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */

    public function getSponsorDetails(Request $request, $sponsorId): JsonResponse
{
    try {
        // Ensure the authenticated user is of type 'sponsor'
        $userId = Auth::user()->id;
        $userProfile = $userId;

        if (!$userProfile) {
            return ApiResponse::error('Failed to fetch sponsor service.', [], 401);
        }

        if ($userProfile !== $sponsorId) {
            return ApiResponse::error('Sponsor ID does not match the authenticated user.', [], 403);
        }

        // Fetch sponsor details from the service
        $sponsorDetails = $this->sponsorService->getSponsorDetails($sponsorId, $userProfile);

        return ApiResponse::success('Sponsor details fetched successfully.', $sponsorDetails);
    } catch (ModelNotFoundException $e) {
        return ApiResponse::error('Sponsor not found.', ['error' => $e->getMessage()], 404);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch sponsor details.', ['error' => $e->getMessage()], 500);
    }
}




public function fundSponsorsWallet(Request $request): JsonResponse
{
    try {
        // Validate request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'paymentGateway' => 'required|string|in:paythru,paystack',
        ]);

        $amount = $validated['amount'];
        $paymentGateway = $validated['paymentGateway'];

    // Fetch the latest sponsor limit (either by creation or update)
    $sponsorLimit = SponsorLimit::latest('updated_at')->first();

    if ($amount >= $sponsorLimit->fund_limit) {
        return response()->json([
            'status' => 'error',
            'message' => "You cannot fund your sponsor wallet with an amount greater than or equal to the set fund limit of {$sponsorLimit->fund_limit}.",
        ], 400);
    }


        // Ensure the authenticated user is of type 'sponsor'
        $userId = Auth::user()->id;
	$user = Auth::user();
	$chek = $this->sponsorService->checkFundLimit($user, $amount);
        // Fund sponsor's wallet based on the payment gateway
        $result = $this->sponsorService->fundSponsorsWallet($amount, $userId, $paymentGateway);

        if ($result['status'] === 'success') {
            return ApiResponse::success('Payment Initaited successfully.', $result['data']);
        }

        return ApiResponse::error($result['message'], [], 400);
    } catch (ValidationException $e) {
        return ApiResponse::error('Validation error.', $e->errors(), 422);
    } catch (ModelNotFoundException $e) {
        return ApiResponse::error('Sponsor not found.', ['error' => $e->getMessage()], 404);
    } catch (\Exception $e) {
        return ApiResponse::error('An unexpected error occurred.', ['error' => $e->getMessage()], 500);
    }
}


public function sponsorWebhookPayment(Request $request)
{
    try {
        $response = $request->all();
        $data = json_decode(json_encode($response));

        Log::info("Starting webhookPaymentResponse", ['data' => $data]);

        if (isset($data->notificationType)) {
            // Handle PayThru webhook response
            $paymentGateway = 'paythru';
            $paymentReference = $data->transactionDetails->paymentReference ?? null;
        } elseif (isset($data->event) && $data->event === 'charge.success') {
            // Handle Paystack webhook response
            $paymentGateway = 'paystack';
            $paymentReference = $data->data->reference ?? null;
        } else {
            Log::warning("Unknown webhook event received", ['data' => $data]);
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook data'], 400);
        }

        if (!$paymentReference) {
            Log::warning("Payment reference not found in webhook data");
            return response()->json(['status' => 'error', 'message' => 'Missing payment reference'], 400);
        }

        // Find payment record
        $payment = VoucherPayment::where('paymentReference', $paymentReference)->first();
        if (!$payment) {
            Log::info("Payment record not found for reference: " . $paymentReference);
            return response()->json(['status' => 'error', 'message' => 'Payment record not found'], 404);
        }

        // Process response based on the payment gateway
        switch ($paymentGateway) {
            case 'paythru':
                $transaction = $data->transactionDetails;
                $payment->payThruReference = $transaction->payThruReference ?? null;
                $payment->fiName = $transaction->fiName ?? null;
                $payment->status = $transaction->status ?? null;
                $payment->amount = $transaction->amount ?? null;
                $payment->responseCode = $transaction->responseCode ?? null;
                $payment->paymentMethod = $transaction->paymentMethod ?? null;
                $payment->commission = $transaction->commission ?? null;
                $payment->residualAmount = $transaction->residualAmount ?? 0;
                $payment->negative_amount = ($transaction->residualAmount < 0) ? $transaction->residualAmount : 0;
                $payment->resultCode = $transaction->resultCode ?? null;
                $payment->responseDescription = $transaction->responseDescription ?? null;
                $payment->providedEmail = $transaction->customerInfo->providedEmail ?? null;
                $payment->providedName = $transaction->customerInfo->providedName ?? null;
                $payment->remarks = $transaction->customerInfo->remarks ?? null;
                break;

            case 'paystack':
                $transaction = $data->data;
                $payment->status = $transaction->status ?? null;
                $payment->amount = ($transaction->amount / 100) ?? null; // Convert from kobo to Naira
                $payment->responseCode = $transaction->gateway_response ?? null;
                $payment->paymentMethod = $transaction->channel ?? null;
                $payment->providedEmail = $transaction->customer->email ?? null;
                $payment->providedName = $transaction->customer->first_name . ' ' . $transaction->customer->last_name ?? null;
                break;
        }

	 $sponsor = SponsorWallet::where('user_id', $payment->sponsor_id)->where('reference', $paymentReference)->first();
        if ($sponsor) {
            // Check if wallet_balance is null or its default value
            if (is_null($sponsor->wallet_balance)) {
                $sponsor->wallet_balance = $payment->amount;
            } else {
                $sponsor->increment('wallet_balance', $payment->amount);
            }
            $sponsor->save();
        }

        $payment->save();
        Log::info("Payment record updated successfully", ['reference' => $paymentReference, 'gateway' => $paymentGateway]);

        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully'], 200);
    } catch (\Exception $e) {
        Log::error("Webhook processing error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['status' => 'error', 'message' => 'Unexpected error occurred'], 500);
    }
}


    }





