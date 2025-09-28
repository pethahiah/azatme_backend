<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Responses\ApiResponse;
use Log;
use App\SponsorLimit;
use App\SponsorWallet;
use App\User;
use DB;


/**
 * @group Vouchers
 *
 * APIs for managing vouchers.
 */

class VoucherController extends Controller
{
    protected $voucherService;


    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    
    
    public function getVoucherBySponsor(Request $request): JsonResponse
    {
        
       $user = Auth::user()->id;

        $perPage = $request->input('per_page', 15);
        $voucherId = $request->input('voucher_id');
    
        try {
            $query = DB::table('vouchers')
                ->where('sponsor_id', $user);
    
            if ($voucherId) {
                $query->where('id', $voucherId);
            }
    
            $vouchers = $query->paginate($perPage);
    
            if ($voucherId && $vouchers->isEmpty()) {
                return ApiResponse::error('Voucher not found.', [], 404);
            }
    
            return ApiResponse::success('Fetched vouchers successfully.', $vouchers);
        } catch (\Exception $e) {
            Log::error('Error fetching vouchers', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to fetch vouchers.', ['error' => $e->getMessage()], 500);
        }
    }

/**
     * Fetch voucher details and businesses assigned to it.
     */
    public function getVoucherWithBusinesses(Request $request)
    {
        $request->validate([
            'voucher_code' => 'required|string|exists:vouchers,voucher_code',
        ]);

        $voucherCode = $request->query('voucher_code');
        $data = $this->voucherService->getVoucherWithBusinesses($voucherCode);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Voucher not found'], 404);
        }

        return response()->json([
            'success' => true,
            'voucher' => $data['voucher'],
            'businesses' => $data['businesses']
        ], 200);
    }


 /**
     * Get total used voucher value and redeemed volume.
     *
     * @return JsonResponse
     */
    public function getUsedVouchersSummary(): JsonResponse
    {
        // Fetch the used vouchers summary (total value and volume)
        $summary = $this->voucherService->getUsedVouchersSummary();

        // Return the response as JSON
        return response()->json([
            'success' => true,
            'message' => 'Used vouchers summary retrieved successfully.',
            'data' => $summary
        ]);
    }



public function getRedeemableVouchers(Request $request, $business_id): JsonResponse
    {

        $vouchers = $this->voucherService->getRedeemableVouchersForBusiness($business_id);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }


/**
     * Get voucher details using voucher code.
     */
    public function getVoucherByCode(Request $request, $voucher_code, $merchant_id): JsonResponse
    {

        $voucher = $this->voucherService->getVoucherByCode($voucher_code, $merchant_id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher not found or not accessible.'], 404);
        }

        return response()->json(['success' => true, 'data' => $voucher]);
    }


 /**
     * Get voucher details by code (for merchants & individuals).
     */
    public function getASingleVoucherByCode(Request $request, $voucherCode): JsonResponse
    {
        $voucher = $this->voucherService->getASingleVoucherByCode($voucherCode);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $voucher]);
    }


 /**
     * Get all vouchers with an optional date range filter.
     */
    public function getVouchers(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $vouchers = $this->voucherService->getVouchersWithDateRange($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ], 200);
    }




 public function getAllVouchers(Request $request): JsonResponse
{
    $perPage = $request->query('per_page', 10);
    $status = $request->query('status');
    $voucherStatus = $request->query('voucher_status');
    $expired = $request->boolean('expired', false); // default is false

    // Fetch vouchers from service
    $vouchers = $this->voucherService->getAllVouchers($voucherStatus, $status, $expired, $perPage);

    return response()->json([
        'success' => true,
        'message' => 'Vouchers retrieved successfully.',
        'data' => $vouchers
    ]);
}


    /**
     * @group Vouchers
     *
     * Create a new voucher.
     *
     * This endpoint allows sponsors to create a new voucher with specified parameters.
     *
     * @bodyParam merchant_id array optional Array of merchant IDs. Example: [1, 2]
     * @bodyParam purpose string optional Purpose of the voucher. Example: "Special Event"
     * @bodyParam voucher_amount number required Total amount of the voucher. Example: 100
     * @bodyParam amount_per_code number required Amount per voucher code. Example: 10
     * @bodyParam expiry_date string optional Expiry date of the voucher in YYYY-MM-DD format. Example: "2024-12-31"
     * @bodyParam limit number optional Limit on the number of uses. Example: 500
     * @bodyParam type string optional Type of voucher (one_time or multiple_time). Example: multiple_time
     * @bodyParam code_generation_method string optional Method for generating codes (sms or qr_code). Example: qr_code
     * @bodyParam location string optional Location where the voucher is valid. Example: "Oyo"
     *
     * @response 201 {
     *     "success": true,
     *     "message": "Voucher created successfully.",
     *     "data": {
     *         "purpose": "Special Event",
     *         "voucher_amount": 100,
     *         "amount_per_code": 10,
     *         "expiry_date": "2024-12-31",
     *         "limit": 500,
     *         "type": "multiple_time",
     *         "code_generation_method": "qr_code",
     *         "location": "Oyo",
     *         "sponsor_id": 1,
     *         "voucher_code": "19AGVCBQOA",
     *         "updated_at": "2024-08-02T17:25:18.000000Z",
     *         "created_at": "2024-08-02T17:25:18.000000Z",
     *         "id": 10,
     *         "merchants": [
     *             {
     *                 "id": 1,
     *                 "user_id": 2,
     *                 "store_name": "olaoluwa store",
     *                 "store_description": "we feed the nation",
     *                 "created_at": "2024-08-02T17:25:18.000000Z",
     *                 "updated_at": "2024-08-02T17:25:18.000000Z",
     *                 "voucher_code": "19AGVCBQOA"
     *             },
     *             {
     *                 "id": 2,
     *                 "user_id": 3,
     *                 "store_name": "olaoluwa store",
     *                 "store_description": "we feed the nation",
     *                 "created_at": "2024-08-02T17:25:18.000000Z",
     *                 "updated_at": "2024-08-02T17:25:18.000000Z",
     *                 "voucher_code": "19AGVCBQOA"
     *             }
     *         ]
     *     }
     * }
     *
     * @response 403 {
     *     "success": false,
     *     "message": "Unauthorized: Only sponsors can create vouchers."
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to create voucher.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->post('/vouchers', [
     *     'json' => [
     *         'merchant_id' => [1, 2],
     *         'purpose' => 'Special Event',
     *         'voucher_amount' => 100,
     *         'amount_per_code' => 10,
     *         'expiry_date' => '2024-12-31',
     *         'limit' => 500,
     *         'type' => 'multiple_time',
     *         'code_generation_method' => 'qr_code',
     *         'location' => 'Oyo',
     *     ],
     * ]);
     */

public function store(Request $request): JsonResponse
{
    // Ensure the authenticated user is of type 'sponsor'
    $user = Auth::user();

// Ensure the authenticated user is of type 'beneficiary'
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsor have access.', [], 403);
    }


    // Validate the request
    $validated = $request->validate([
        'purpose' => 'nullable|string',
        'voucher_amount' => 'required|numeric',
        'amount_per_code' => 'required|numeric',
        'expiry_date' => 'nullable|date',
        'limit' => 'nullable|numeric',
	    'sponsor_id' => 'nullable|numeric',
        'type' => 'nullable|in:one_time,multiple_time',
        'code_generation_method' => 'nullable|in:sms,qr_code',
        'state' => 'nullable|string',
        'business_id' => 'nullable|array',
        'business_id.*' => 'nullable|exists:users,id',
    ]);

    $voucherAmount = $validated['voucher_amount'];
    $amountPerCode = $validated['amount_per_code'];

    // Fetch the latest sponsor limit (either by creation or update)
    $sponsorLimit = SponsorLimit::latest('updated_at')->first();

    // Check if the amount_per_code exceeds or equals the voucher limit
    if ($amountPerCode >= $sponsorLimit->voucher_limit) {
        return response()->json([
            'status' => 'error',
            'message' => "You cannot create a voucher per beneficiary user that is greater than or equal to the set voucher limit of {$sponsorLimit->voucher_limit}.",
        ], 400);
    }


// Fetch sponsor's wallet balance
    $sponsorWallet = SponsorWallet::where('user_id', $user->id)->first();

    if (!$sponsorWallet || $sponsorWallet->wallet_balance < $voucherAmount) {
        return response()->json([
            'status' => 'error',
            'message' => 'Insufficient wallet balance to create the voucher or the sponsor is yet to fund wallet.',
        ], 400);
    }


    // Add the sponsor ID
    $validated['user_id'] = $user->id;
    try {
        // Call the voucher service to create the voucher
        $voucher = $this->voucherService->createVoucher($validated);

        // Prepare the response data
        $voucherData = $voucher->toArray();
        $voucherData['businesses'] = $voucher->businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'business_id' => $business->owner_id,
                'business_name' => $business->business_name,
                'business_description' => $business->description,
                'created_at' => $business->pivot->created_at,
                'updated_at' => $business->pivot->updated_at,
                'voucher_code' => $business->pivot->voucher_code,
            ];
        }) ?? [];

        return ApiResponse::success('Voucher created successfully.', $voucherData, 201);
    } catch (\Exception $e) {
        \Log::error('Voucher Creation Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'user_id' => $user->id,
            'input' => $validated,
        ]);

        return ApiResponse::error('Failed to create voucher.', ['error' => $e->getMessage()], 500);
    }
}

public function claim(Request $request): JsonResponse
{
    $user = Auth::user();

    $request->validate([
        'voucher_code' => 'required|string',
    ]);

    $ipAddress = $request->ip();

    try {
        $result = $this->voucherService->claimVoucher($request->voucher_code, $user->id, $ipAddress);

        if ($result['success']) {
            return ApiResponse::success($result['message'], $result['voucher'], 200);
        }

        return ApiResponse::error($result['message'], [], 400);

    } catch (\Exception $e) {
        return ApiResponse::error('Unexpected error occurred.', ['error' => $e->getMessage()], 500);
    }
}



    /**
     * @group Vouchers
     *
     * Update an existing voucher.
     *
     * This endpoint allows updating the details of a voucher.
     *
     * @urlParam voucherId int required The ID of the voucher to update. Example: 10
     *
     * @bodyParam merchant_id array required List of merchant IDs associated with the voucher. Example: [1]
     * @bodyParam purpose string required Purpose of the voucher. Example: "Special Discount"
     * @bodyParam expiry_date string required Expiry date of the voucher in YYYY-MM-DD format. Example: "2024-12-31"
     * @bodyParam limit number required Limit on the number of uses. Example: 100
     * @bodyParam voucher_amount number required Total amount of the voucher. Example: 50.00
     * @bodyParam amount_per_code number required Amount per voucher code. Example: 5.00
     * @bodyParam type string required Type of voucher (one_time or multiple_time). Example: one_time
     * @bodyParam code_generation_method string required Method for generating codes (qr_code or sms). Example: qr_code
     * @bodyParam location string required Location where the voucher is valid. Example: "Lagos"
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Voucher updated successfully.",
     *     "data": {
     *         "response": {
     *             "id": 10,
     *             "voucher_code": "19AGVCBQOA",
     *             "sponsor_id": 1,
     *             "purpose": "Special Discount",
     *             "expiry_date": "2024-12-31",
     *             "limit": 100,
     *             "voucher_amount": 50.00,
     *             "amount_per_code": 5.00,
     *             "location": "Lagos",
     *             "type": "one_time",
     *             "voucher_status": "unused",
     *             "code_generation_method": "qr_code",
     *             "deleted_at": null,
     *             "created_at": "2024-08-02T17:25:18.000000Z",
     *             "updated_at": "2024-08-02T17:29:05.000000Z",
     *             "merchants": [
     *                 {
     *                     "id": 1,
     *                     "user_id": 2,
     *                     "store_name": "olaoluwa store",
     *                     "store_description": "we feed the nation",
     *                     "voucher_code": null,
     *                     "deleted_at": null,
     *                     "created_at": "2024-08-02T17:05:28.000000Z",
     *                     "updated_at": "2024-08-02T17:05:28.000000Z",
     *                     "pivot": {
     *                         "voucher_id": 10,
     *                         "merchant_id": 1,
     *                         "voucher_code": "19AGVCBQOA",
     *                         "created_at": "2024-08-02T17:25:18.000000Z",
     *                         "updated_at": "2024-08-02T17:29:05.000000Z"
     *                     }
     *                 }
     *             ]
     *         }
     *     }
     * }
     *
     * @response 403 {
     *     "success": false,
     *     "message": "Unauthorized: Only sponsors can update vouchers."
     * }
     *
     * @response 404 {
     *     "success": false,
     *     "message": "Voucher not found."
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to update voucher.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->post('/update/10/voucher', [
     *     'json' => [
     *         'merchant_id' => [1],
     *         'purpose' => 'Special Discount',
     *         'expiry_date' => '2024-12-31',
     *         'limit' => 100,
     *         'voucher_amount' => 50.00,
     *         'amount_per_code' => 5.00,
     *         'type' => 'one_time',
     *         'code_generation_method' => 'qr_code',
     *         'location' => 'Lagos',
     *     ],
     * ]);
     */

    public function update(Request $request, int $voucherId): JsonResponse
    {
         // Ensure the authenticated user is of type 'sponsor'
    $user = Auth::user();

// Ensure the authenticated user is of type 'beneficiary'
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsor have access.', [], 403);
    }

        // Validate the request with optional fields
        $validated = $request->validate([
            'business_id' => 'nullable|array',
            'business_id.*' => 'nullable|exists:merchants,id',
            'purpose' => 'nullable|string',
            'voucher_amount' => 'nullable|numeric',
            'amount_per_code' => 'nullable|numeric',
            'expiry_date' => 'nullable|date',
            'limit' => 'nullable|numeric',
            'type' => 'nullable|in:one_time,multiple_time',
            'code_generation_method' => 'nullable|in:sms,qr_code',
            'location' => 'nullable|string',
        ]);

        // Add sponsor_id to validated data
        $validated['sponsor_id'] = $user->id;

        try {
            // Call the service to update the voucher
            $voucher = $this->voucherService->updateVoucher($voucherId, $validated);

            return ApiResponse::success('Voucher updated successfully.', $voucher, 200);

        } catch (ModelNotFoundException $e) {
            // Return an error response when the voucher is not found
            return ApiResponse::error('Voucher not found.', ['error' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            // Return an error response for query-related issues
            return ApiResponse::error('Failed to update voucher.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Soft delete a voucher.
     *
     * @param Request $request
     * @param int $VoucherId
     * @return JsonResponse
     */



    /**
     * Soft delete a voucher.
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Voucher revoked successfully."
     * }
     *
     * @response 403 {
     *     "success": false,
     *     "message": "Unauthorized: Only admin sponsors can revoke vouchers."
     * }
     *
     * @response 404 {
     *     "success": false,
     *     "message": "Voucher not found.",
     *     "error": "Detailed error message"
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to revoke voucher.",
     *     "error": "Detailed error message"
     * }
     */
    public function revoke(Request $request, int $VoucherId): JsonResponse
    {

          // Get the authenticated user's ID and usertype
    $user = Auth::user();

    // Ensure the authenticated user is of type 'beneficiary'
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsor have access.', [], 403);
    }    
        try {
            // Call the service to soft delete the voucher
            $this->voucherService->revokeVoucher($VoucherId);

            return ApiResponse::success('Voucher revoked successfully.', null, 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Voucher not found.', ['error' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            return ApiResponse::error('Failed to revoke voucher.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Permanently delete a voucher.
     *
     * @param Request $request
     * @param int $VoucherId
     * @return JsonResponse
     */



    /**
     * Permanently delete a voucher.
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Voucher deleted successfully."
     * }
     *
     * @response 403 {
     *     "success": false,
     *     "message": "Unauthorized: Only admin sponsors can delete vouchers."
     * }
     *
     * @response 404 {
     *     "success": false,
     *     "message": "Voucher not found.",
     *     "error": "Detailed error message"
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to delete voucher.",
     *     "error": "Detailed error message"
     * }
     */

    public function destroy(Request $request, int $VoucherId): JsonResponse
    {
          $user = Auth::user();

    // Ensure the authenticated user is of type 'beneficiary'
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsor have access.', [], 403);
    }

        try {
            $this->voucherService->deleteVoucher($VoucherId);
            return ApiResponse::success('Voucher deleted successfully.', null, 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Voucher not found.', ['error' => $e->getMessage()], 404);

        } catch (QueryException $e) {
            return ApiResponse::error('Failed to revoke voucher.', ['error' => $e->getMessage()], 500);

        }
    }


    /**
     * @group Vouchers
     *
     * Redeem a voucher.
     *
     * This endpoint allows users to redeem a voucher by providing its code.
     *
     * @urlParam voucher_code string required The code of the voucher to redeem. Example: "19AGVCBQOA"
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Voucher redeemed successfully.",
     *     "data": {
     *         "response": null
     *     }
     * }
     *
     * @response 400 {
     *     "success": false,
     *     "message": "Voucher has expired."
     * }
     *
     * @response 400 {
     *     "success": false,
     *     "message": "Voucher has already been used."
     * }
     *
     * @response 400 {
     *     "success": false,
     *     "message": "No more vouchers available."
     * }
     *
     * @response 400 {
     *     "success": false,
     *     "message": "Voucher is not valid in your location."
     * }
     *
     * @response 404 {
     *     "success": false,
     *     "message": "Voucher not found."
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to redeem voucher.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->post('/vouchers/redeem', [
     *     'json' => [
     *         'voucher_code' => '19AGVCBQOA',
     *     ],
     * ]);
     */

    public function redeem(Request $request): JsonResponse
{
    $user = Auth::user();
    // Ensure the authenticated user is of type 'beneficiary'
    if ($user->usertype !== 'merchant') {
        return ApiResponse::error(' Only users with the merchant type have access.', [], 403);
    }
    $email = $request->email;
    $findEmail = User::where('email', $email)->first();
    $userProfile = $findEmail->id;
    $validated = $request->validate([
        'voucher_code' => 'required|string',
    ]);

    $ipAddress = $request->ip();
//return $ipAddress;
    try {
        $result = $this->voucherService->redeemVoucher($validated['voucher_code'], $ipAddress, $userProfile);
//dd($result);
        if ($result['success']) {
            return ApiResponse::success('Voucher redeemed successfully.');
        }

        $statusCode = 500;
        switch ($result['message'] ?? 'Unknown error') {
            case 'Voucher has expired.':
            case 'Voucher has already been used.':
            case 'No more vouchers available.':
            case 'Voucher is not valid in your location.':
                $statusCode = 400;
                break;
            case 'Voucher not found.':
                $statusCode = 404;
                break;
        }

        return ApiResponse::error($result['message'], [], $statusCode);
    } catch (ModelNotFoundException $e) {
        return ApiResponse::error('Voucher not found.', ['error' => $e->getMessage()], 404);
    } catch (QueryException $e) {
        return ApiResponse::error('Failed to redeem voucher.', ['error' => $e->getMessage()], 500);
    } catch (\Exception $e) {
        return ApiResponse::error('An unexpected error occurred.', ['error' => $e->getMessage()], 500);
    }
}


    /**
     * @group Vouchers
     *
     * Get vouchers by date range.
     *
     * This endpoint allows sponsors to fetch vouchers within a specified date range.
     *
     * @urlParam start_date string required The start date of the date range. Format: YYYY-MM-DD. Example: "2024-01-01"
     * @urlParam end_date string required The end date of the date range. Format: YYYY-MM-DD. Example: "2024-12-31"
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Vouchers fetched successfully.",
     *     "data": [
     *         {
     *             "id": 10,
     *             "voucher_code": "19AGVCBQOA",
     *             "purpose": "Special Event",
     *             "expiry_date": "2024-12-31",
     *             "amount_per_code": 10,
     *             "voucher_amount": 100,
     *             "location": "Oyo",
     *             "type": "multiple_time",
     *             "code_generation_method": "qr_code",
     *             "created_at": "2024-08-02T17:25:18.000000Z",
     *             "updated_at": "2024-08-02T17:25:18.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 403 {
     *     "success": false,
     *     "message": "Unauthorized: Only sponsors can access their vouchers."
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch vouchers.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/vouchers/date-range', [
     *     'query' => [
     *         'start_date' => '2024-01-01',
     *         'end_date' => '2024-12-31'
     *     ],
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */


public function getVouchersByDateRange(Request $request): JsonResponse
{
    // Validate the request parameters
    $validated = $request->validate([
        'start_date' => 'required|date',  // Ensure start_date is a valid date
        'end_date' => 'required|date',    // Ensure end_date is a valid date
    ]);

    // Ensure the authenticated user is a sponsor
    $user = Auth::user();
    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsors have access.', [], 403);
    }

    $userId = $user->id;
    $startDate = $validated['start_date'];
    $endDate = $validated['end_date'];

    try {
        // Fetch vouchers by date range using voucherService
        $vouchers = $this->voucherService->getVouchersByDateRange($userId, $startDate, $endDate);

        if ($vouchers->isEmpty()) {
            return ApiResponse::error('No vouchers found for the given date range.', [], 404);
        }

        return ApiResponse::success('Vouchers fetched successfully.', $vouchers);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch vouchers.', ['error' => $e->getMessage()], 500);
    }
}


    /**
     * @group Vouchers
     *
     * Get used vouchers.
     *
     * This endpoint retrieves all used vouchers with pagination support.
     *
     * @queryParam per_page int The number of vouchers per page. Default is 15. Example: 15
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Fetched used vouchers successfully.",
     *     "data": [
     *         {
     *             "id": 10,
     *             "voucher_code": "19AGVCBQOA",
     *             "purpose": "Special Event",
     *             "expiry_date": "2024-12-31",
     *             "amount_per_code": 10,
     *             "voucher_amount": 100,
     *             "location": "Oyo",
     *             "type": "multiple_time",
     *             "code_generation_method": "qr_code",
     *             "used_at": "2024-08-02T17:25:18.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch used vouchers.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/vouchers/used', [
     *     'query' => [
     *         'per_page' => 15
     *     ],
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */

public function getUsedVouchersBySponsor(Request $request, $sponsorId): JsonResponse
{

    $perPage = $request->input('per_page', 15);  // Pagination per page

    try {
        // Fetch used vouchers by sponsor
        $vouchers = $this->voucherService->getUsedVouchersBySponsor($sponsorId, $perPage);

        return ApiResponse::success('Fetched used vouchers successfully.', $vouchers);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch used vouchers.', ['error' => $e->getMessage()], 500);
    }
}

public function getUsedVouchersByOwner(Request $request): JsonResponse
{
    // Ensure the authenticated user is of type 'sponsor'
    $user = Auth::user();

    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsors have access.', [], 403);
    }

    $userId = $user->id;  // Get the authenticated user's ID
    $perPage = $request->input('per_page', 15);  // Pagination per page

    try {
        // Fetch used vouchers by sponsor
        $vouchers = $this->voucherService->getUsedVouchersBySponsor($userId, $perPage);

        return ApiResponse::success('Fetched used vouchers successfully.', $vouchers);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch used vouchers.', ['error' => $e->getMessage()], 500);
    }
}


public function getBeneficiariesWithRedeemedVouchersBySponsor(Request $request, $sponsorId): JsonResponse
{
    $perPage = $request->input('per_page', 15);  // Pagination per page

    try {
        // Call service method to fetch beneficiaries with redeemed vouchers for a specific sponsor
        $beneficiaries = $this->voucherService->getBeneficiariesWithRedeemedVouchersBySponsor($sponsorId, $perPage);

        return ApiResponse::success('Fetched beneficiaries with redeemed vouchers for sponsor successfully.', $beneficiaries);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch beneficiaries with redeemed vouchers for sponsor.', ['error' => $e->getMessage()], 500);
    }
}


public function getBeneficiariesWithRedeemedVouchersByOwner(Request $request): JsonResponse
{
    // Ensure the authenticated user is of type 'sponsor'
    $user = Auth::user();

    if ($user->usertype !== 'sponsor') {
        return ApiResponse::error('Only sponsors have access.', [], 403);
    }

    $userId = $user->id;  // Get the authenticated user's ID
    $perPage = $request->input('per_page', 15);  // Pagination per page

    try {
        // Call service method to fetch beneficiaries with redeemed vouchers for the authenticated sponsor
        $beneficiaries = $this->voucherService->getBeneficiariesWithRedeemedVouchersByOwner($userId, $perPage);

        return ApiResponse::success('Fetched beneficiaries with redeemed vouchers for owner successfully.', $beneficiaries);
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to fetch beneficiaries with redeemed vouchers for owner.', ['error' => $e->getMessage()], 500);
    }
}





    /**
     * @group Vouchers
     *
     * Get redeemed vouchers.
     *
     * This endpoint retrieves all redeemed vouchers with pagination support.
     *
     * @queryParam per_page int The number of vouchers per page. Default is 15. Example: 15
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Fetched redeemed vouchers successfully.",
     *     "data": [
     *         {
     *             "id": 10,
     *             "voucher_code": "19AGVCBQOA",
     *             "purpose": "Special Event",
     *             "expiry_date": "2024-12-31",
     *             "amount_per_code": 10,
     *             "voucher_amount": 100,
     *             "location": "Oyo",
     *             "type": "multiple_time",
     *             "code_generation_method": "qr_code",
     *             "redeemed_at": "2024-08-02T17:25:18.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch redeemed vouchers.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/vouchers/redeemed', [
     *     'query' => [
     *         'per_page' => 15
     *     ],
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */

    public function getRedeemedVouchers(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        try {
            $vouchers = $this->voucherService->getRedeemedVouchers($perPage);
            return ApiResponse::success('Fetched redeemed vouchers successfully.', $vouchers);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch redeemed vouchers.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * @group Vouchers
     *
     * Get beneficiaries with redeemed vouchers.
     *
     * This endpoint retrieves beneficiaries who have redeemed vouchers with pagination support.
     *
     * @queryParam per_page int The number of beneficiaries per page. Default is 15. Example: 15
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Fetched beneficiaries with redeemed vouchers successfully.",
     *     "data": [
     *         {
     *             "id": 1,
     *             "name": "John Doe",
     *             "redeemed_vouchers_count": 5
     *         }
     *     ]
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch beneficiaries.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/beneficiaries/redeemed', [
     *     'query' => [
     *         'per_page' => 15
     *     ],
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */

    public function getBeneficiariesWithRedeemedVouchers(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        try {
            $beneficiaries = $this->voucherService->getBeneficiariesWithRedeemedVouchers($perPage);
            return ApiResponse::success('Fetched beneficiaries with redeemed vouchers successfully.', $beneficiaries);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch beneficiaries.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * @group Vouchers
     *
     * Get vouchers yet to be redeemed.
     *
     * This endpoint retrieves vouchers that have not yet been redeemed with pagination support.
     *
     * @queryParam per_page int The number of vouchers per page. Default is 15. Example: 15
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Fetched vouchers yet to be redeemed successfully.",
     *     "data": [
     *         {
     *             "id": 10,
     *             "voucher_code": "19AGVCBQOA",
     *             "purpose": "Special Event",
     *             "expiry_date": "2024-12-31",
     *             "amount_per_code": 10,
     *             "voucher_amount": 100,
     *             "location": "Oyo",
     *             "type": "multiple_time",
     *             "code_generation_method": "qr_code",
     *             "created_at": "2024-08-02T17:25:18.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch vouchers yet to be redeemed.",
     *     "error": "Detailed error message"
     * }
     *
     * @example php
     * $response = $client->get('/vouchers/yet-to-be-redeemed', [
     *     'query' => [
     *         'per_page' => 15
     *     ],
     *     'headers' => [
     *         'Authorization' => 'Bearer YOUR_TOKEN_HERE'
     *     ]
     * ]);
     */

    public function getVouchersYetToBeRedeemed(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        try {
            $vouchers = $this->voucherService->getVouchersYetToBeRedeemed($perPage);
            return ApiResponse::success('Fetched vouchers yet to be redeemed successfully.', $vouchers);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch vouchers yet to be redeemed.', ['error' => $e->getMessage()], 500);
        }
    }



}

