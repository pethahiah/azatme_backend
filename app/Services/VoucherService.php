<?php

namespace App\Services;

use App\Transaction;
use App\VoucherTransaction;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Bank;
use App\Business;
use App\MerchantPayout;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;


class VoucherService
{
    
    
    public function createVoucher(array $data)
{
    // Generate a unique voucher code
    $data['voucher_code'] = Str::upper(Str::random(10));

    if (!isset($data['sponsor_id'])) {
        throw new \Exception('Missing sponsor_id.');
    }

    // Create the voucher
    $voucher = Voucher::create($data);

    // If business_ids are provided, associate them with the voucher
    if (isset($data['business_id']) && is_array($data['business_id'])) {
        // Get the state from the voucher
        $voucherState = $voucher->state;

        // Prepare pivot data and track invalid businesses
        $pivotData = [];
        $invalidBusinesses = [];

        foreach ($data['business_id'] as $businessId) {
            // Find the business
            $business = Business::find($businessId);

            // If the business exists and the state matches the voucher's state, add it
            if ($business && $business->state === $voucherState) {
                $pivotData[$businessId] = [
                    'voucher_code' => $data['voucher_code'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            } else {
                // If business state doesn't match, add to invalid businesses list
                $invalidBusinesses[] = $businessId;
            }
        }

        // Sync only the valid businesses with the voucher
        if (!empty($pivotData)) {
            $voucher->businesses()->sync($pivotData);
        }

        // Provide feedback about invalid businesses
        if (!empty($invalidBusinesses)) {
            // Return a warning message indicating which businesses were invalid
            return [
                'voucher' => $voucher,
                'message' => 'Some businesses were not added because they are not from the same state as the voucher.',
                'invalid_businesses' => $invalidBusinesses
            ];
        }
    }

    // Reload the voucher with businesses and the pivot data
    $voucher->load('businesses');

    return $voucher;
}

    public function updateVoucher(int $id, array $data): Voucher
    {
        try {
            // Find the voucher by ID
            $voucher = Voucher::findOrFail($id);

            // Update the voucher's attributes
            $voucher->update($data);

            // If business_ids are provided, update the associated businesses
            if (isset($data['business_id']) && is_array($data['business_id'])) {
                // Prepare pivot data if necessary, e.g., for timestamps or additional fields
                $pivotData = array_fill_keys($data['business_id'], ['voucher_code' => $voucher->voucher_code]);

                // Sync businesses with the voucher, keeping pivot data
                $voucher->businesses()->sync($pivotData);
            }

            // Reload the voucher with businesses and pivot data
            $voucher->load('businesses');

            return $voucher;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Voucher not found.");
        } catch (QueryException $e) {
            throw new QueryException("Failed to update voucher: " . $e->getMessage(), $e->errorInfo, $e);
        }
    }

    /**
     * Soft delete a voucher.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function revokeVoucher(int $id): bool
    {
        try {
            $voucher = Voucher::findOrFail($id);
            return $voucher->delete();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Voucher not found.");
        } catch (QueryException $e) {
            throw new QueryException("Failed to revoke voucher: " . $e->getMessage(), $e->errorInfo, $e);
        }
    }

    /**
     * Permanently delete a voucher.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteVoucher(int $id): bool
    {
        try {
            $voucher = Voucher::findOrFail($id);

            // Detach associated businesses if necessary
            $voucher->businesses()->detach();

            return $voucher->forceDelete();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Voucher not found.");
        } catch (QueryException $e) {
            throw new QueryException("Failed to delete voucher: " . $e->getMessage(), $e->errorInfo, $e);
        }
    }

    /**
     * Redeem a voucher and validate location.
     *
     * @param string $voucherCode
     * @param string $ipAddress
     * @return array
     */
public function redeemVoucher($voucherCode, $ipAddress, $userProfile): array
{
    Log::info("Attempting to redeem voucher", [
        'voucher_code' => $voucherCode,
        'ip_address' => $ipAddress,
        'email' => $userProfile
    ]);

    // Find the voucher by code
    $voucher = Voucher::where('voucher_code', $voucherCode)->first();

    if (!$voucher) {
        Log::warning("Voucher not found", ['voucher_code' => $voucherCode]);
        return ['success' => false, 'message' => 'Voucher not found.'];
    }

    // ?? Non-transferability check
    if ($voucher->claimed_by !== null && $voucher->claimed_by !== $userProfile) {
        Log::warning("Voucher claimed by another user", [
            'voucher_code' => $voucherCode,
            'claimed_by' => $voucher->claimed_by,
            'attempted_by' => $userProfile
        ]);
        return ['success' => false, 'message' => 'This voucher has been claimed by another user.'];
    }

    // Retrieve business_id from business_voucher table
    $businessVoucher = DB::table('business_voucher')
        ->where('voucher_id', $voucher->id)
        ->where('voucher_code', $voucherCode)
        ->first();

    if (!$businessVoucher) {
        Log::warning("No business found for this voucher", ['voucher_code' => $voucherCode]);
        return ['success' => false, 'message' => 'Voucher is not associated with any business.'];
    }

    $businessId = $businessVoucher->business_id;

    // Check if the voucher has expired
    if ($voucher->expiry_date < now()) {
        Log::warning("Voucher expired", ['voucher_code' => $voucherCode, 'expiry_date' => $voucher->expiry_date]);
        return ['success' => false, 'message' => 'Voucher has expired.'];
    }

    Log::info("Voucher expiry date valid", ['voucher_code' => $voucherCode]);

    // Check if the user has already redeemed this voucher
    $existingTransaction = VoucherTransaction::where('voucher_id', $voucher->id)
        ->where('user_id', $userProfile)
        ->exists();

    if ($existingTransaction) {
        Log::warning("User has already redeemed this voucher", [
            'voucher_code' => $voucherCode,
            'user_id' => $userProfile
        ]);
        return ['success' => false, 'message' => 'Voucher already redeemed by this user.'];
    }

    // Check if the voucher has already been used (one-time type)
    if ($voucher->type === 'one_time' && $voucher->voucher_status === 'used') {
        Log::warning("Voucher already used", ['voucher_code' => $voucherCode]);
        return ['success' => false, 'message' => 'Voucher has already been used.'];
    }

    // Validate the beneficiary's location
    $beneficiaryLocation = $this->getLocationByIp($ipAddress);

    Log::info("Beneficiary location determined", ['ip_address' => $ipAddress, 'location' => $beneficiaryLocation]);
    $latitude = $beneficiaryLocation['latitude'] ?? null;
    $longitude = $beneficiaryLocation['longitude'] ?? null;
    $city = $beneficiaryLocation['city'] ?? null;
    $state = $beneficiaryLocation['state'] ?? null;

    // Determine the device (User-Agent)
    $device = $request->input('device') ?? $request->header('User-Agent');
    // Check if voucher location matches beneficiary location
    if ($voucher->state !== null && $voucher->state !== $beneficiaryLocation) {
        Log::warning("Voucher location mismatch", [
            'voucher_code' => $voucherCode,
            'voucher_location' => $voucher->location,
            'beneficiary_location' => $beneficiaryLocation
        ]);
        return ['success' => false, 'message' => 'Voucher is not valid in your location.'];
    }

    Log::info("Voucher location valid", ['voucher_code' => $voucherCode]);

    // Begin database transaction
    DB::beginTransaction();

    try {
        // Reduce the voucher limit and update status if necessary
        if ($voucher->limit > 0) {
            $voucher->limit -= 1;
            $voucher->voucher_status = 'used';
            // Optional: Set claimed_by during redemption if not yet set
            if ($voucher->claimed_by === null) {
                $voucher->claimed_by = $userProfile;
                $voucher->claimed_at = now();
            }
            $voucher->save();
            Log::info("Voucher limit updated", ['voucher_code' => $voucherCode, 'new_limit' => $voucher->limit]);
        } else {
            Log::warning("Voucher limit reached zero", ['voucher_code' => $voucherCode]);
            DB::rollBack();
            return ['success' => false, 'message' => 'No more vouchers available.'];
        }

        // Create a transaction record
        $transaction = VoucherTransaction::create([
            'voucher_id' => $voucher->id,
            'user_id' => $userProfile,
            'business_id' => $businessId,
            'amount' => $voucher->amount_per_code,
            'status' => 'used',
            'type' => $voucher->type,
            'code_generation_method' => $voucher->code_generation_method,
            'payout_status' => 'pending',
            'redeemed_at' => now(),
            'city' => $city,
            'state' => $state,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'device' => $device
        ]);

        Log::info("Transaction created", ['transaction' => $transaction->toArray()]);

        DB::commit();

        // Retrieve user details
        $user = User::find($userProfile);

        Log::info("Voucher redeemed successfully", ['voucher_code' => $voucherCode]);

        return [
            'success' => true,
            'message' => 'Voucher redeemed successfully.',
            'transaction' => $transaction,
            'user' => $user
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error redeeming voucher", [
            'voucher_code' => $voucherCode,
            'error' => $e->getMessage()
        ]);
        return ['success' => false, 'message' => 'Failed to redeem voucher.'];
    }
}



public function claimVoucher($voucherCode, $userId, $ipAddress)
{
    $voucher = Voucher::where('voucher_code', $voucherCode)->first();

    if (!$voucher) {
        return ['success' => false, 'message' => 'Voucher not found.'];
    }

    if ($voucher->claimed_by !== null) {
        return ['success' => false, 'message' => 'Voucher has already been claimed.'];
    }

    if ($voucher->expiry_date && $voucher->expiry_date < now()) {
        return ['success' => false, 'message' => 'Voucher has expired.'];
    }
    

    $location = $this->getLocationByIp($ipAddress);
    if ($voucher->state !== null && $voucher->state !== $location) {
        return [
            'success' => false,
            'message' => "Voucher is not valid in your location. Your location: {$location}, Allowed: {$voucher->state}."
        ];
    }


    try {
        $voucher->claimed_by = $userId;
        $voucher->claimed_at = now();
        $voucher->save();

        return ['success' => true, 'message' => 'Voucher claimed successfully.', 'voucher' => $voucher];
    } catch (\Exception $e) {
        Log::error("Failed to claim voucher", ['voucher_code' => $voucherCode, 'error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Failed to claim voucher.'];
    }
}

    /**
     * Get location based on IP address using a geolocation service.
     *
     * @param string $ipAddress
     * @return string
     */

private function getLocationByIp(string $ipAddress): string
{
    $response = Http::get("http://ipinfo.io/{$ipAddress}/json");

    if ($response->successful()) {
        $data = $response->json();
        return $data['region'] ?? 'Unknown';
    }

    return 'Unknown';
}

/**
 * Get Voucher details and associated businesses using voucher_code.
 */
public function getVoucherWithBusinesses($voucherCode)
{
    // Fetch the voucher details
    $voucher = Voucher::where('voucher_code', $voucherCode)->first();

    if (!$voucher) {
        return null;
    }

    // Get businesses linked to this voucher and in the same state as the voucher
    $businesses = Business::select('businesses.*')
        ->join('business_voucher', 'businesses.id', '=', 'business_voucher.business_id')
        ->where('business_voucher.voucher_code', $voucherCode)
        ->where('businesses.state', $voucher->state)
        ->get();

    return [
        'voucher' => $voucher,
        'businesses' => $businesses
    ];
}



/**
     * Get total value of used vouchers and total volume of redeemed vouchers.
     *
     * @return array
     */
    public function getUsedVouchersSummary(): array
    {
        // Get the sum of all voucher_amount where voucher_status is 'used'
        $totalValue = Voucher::where('voucher_status', 'used')->sum('voucher_amount');

        // Get the count of all vouchers where voucher_status is 'used'
        $totalVolume = Voucher::where('voucher_status', 'used')->count();

        return [
            'total_voucher_used_value' => $totalValue,
            'total_voucher_redeemed_volume' => $totalVolume,
        ];
    }



public function getRedeemableVouchersForBusiness($businessId)
{
    return Voucher::select('vouchers.*', 'business_voucher.voucher_code', 'business_voucher.created_at as assigned_at')
        ->join('business_voucher', 'vouchers.id', '=', 'business_voucher.voucher_id')
        ->where('business_voucher.business_id', $businessId)
        ->where('vouchers.expiry_date', '>=', Carbon::now())
        ->whereNull('vouchers.deleted_at')
        ->where('vouchers.voucher_status', 'unused')
        ->orderBy('business_voucher.created_at', 'desc')
        ->get();
}


  /**
     * Get voucher details using voucher code and beneficiary email.
     */
    public function getVoucherByCode($voucherCode, $merchantId)
    {
        $voucher = Voucher::select('vouchers.*', 'business_voucher.voucher_code', 'business_voucher.created_at as assigned_at')
            ->join('business_voucher', 'vouchers.id', '=', 'business_voucher.voucher_id')
            ->where('business_voucher.business_id', $merchantId)
            ->where('business_voucher.voucher_code', $voucherCode)
            ->where('vouchers.expiry_date', '>=', Carbon::now())
            ->whereNull('vouchers.deleted_at')
            ->where('vouchers.voucher_status', 'unused')
            ->first();

        return $voucher;
    }


 /**
     * Get voucher details by code (for merchants & individuals).
     */
    public function getASingleVoucherByCode($voucherCode)
    {
        return Voucher::where('voucher_code', $voucherCode)
            ->whereNull('deleted_at')
            ->first();
    }

  /**
     * Get all vouchers with an optional date range filter.
     */
    public function getVouchersWithDateRange($startDate = null, $endDate = null)
{
    $query = Voucher::whereNull('deleted_at')
                    ->where('voucher_status', 'unused');

    // Apply date range filter if provided
    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    return $query->orderBy('created_at', 'desc')->get();
}



public function initiatePayout()
{

   $merchantId = Auth::user()->id;
    // Fetch the merchant (seller) from the database
    $seller = Business::where('owner_id', $merchantId)->first();

    if (!$seller) {
        Log::error('Merchant not found');
    }

    // Calculate total pending amount for the merchant
    $amount = VoucherTransaction::where('business_id', $merchantId)
        ->where('status', 'used')
        ->where('payout_status', 'pending')
        ->sum('amount');

    if ($amount <= 0) {
         Log::error('No pending amount to payout');
    }

    // Call transfer function
    return $this->transferToSeller($seller, $amount, $merchantId);
}

public function transferToSeller($seller, $amount, $merchantId)
{
    try {
        // Create payout record before sending money
        $payout = MerchantPayout::create([
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'status' => 'pending',
             ]);

        $getBank = Bank::where('user_id', $merchantId)->first();
        if (!$getBank) {
            Log::error('Bank details not found for merchant', ['merchant_id' => $merchantId]);
            $payout->update(['status' => 'failed']);
            return;
        }

        // Create Transfer Recipient
        $recipientResponse = Http::withToken(env('PAYSTACK_SECRET_KEY'))->post('https://api.paystack.co/transferrecipient', [
            'type' => 'nuban',
            'name' => $getBank->account_name,
            'account_number' => $getBank->account_number,
            'bank_code' => $getBank->bankCode,
        ]);

        $recipientData = $recipientResponse->json();
        if (!isset($recipientData['data']['recipient_code'])) {
            Log::error('Failed to create transfer recipient', [
                'merchant_id' => $merchantId,
                'response' => $recipientData,
            ]);
            $payout->update(['status' => 'failed']);
            return;
        }

        $recipientCode = $recipientData['data']['recipient_code'];

        // Initiate Transfer
        $transferResponse = Http::withToken(env('PAYSTACK_SECRET_KEY'))->post('https://api.paystack.co/transfer', [
            'source' => 'balance',
            'amount' => $amount * 100,
            'recipient' => $recipientCode,
            'reason' => 'Payment for redeemed Voucher',
        ]);

        $transferData = $transferResponse->json();

        if ($transferData['status']) {
            // Update payout record and mark transactions as paid
            $payout->update([
                'status' => 'success',
                'transaction_reference' => $transferData['data']['reference'],
            ]);

            Log::info('Payout successful', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'transaction_reference' => $transferData['data']['reference'],
            ]);
        } else {
            // Mark as failed if Paystack returns error
            $payout->update(['status' => 'failed']);

            Log::error('Payout failed', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'response' => $transferData,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Transfer to seller failed', [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'error' => $e->getMessage(),
        ]);

        $payout->update(['status' => 'failed']);
    }
}




public function getAllVouchers($voucherStatus = null, $status = null, $expired = false, $perPage = 10)
{
    $query = Voucher::query();

    // Filter by voucher status if provided
    if (in_array($voucherStatus, ['used', 'unused'])) {
        $query->where('voucher_status', $voucherStatus);
    }

    // Filter by approval status if provided
    if (in_array($status, ['pending', 'approved', 'rejected'])) {
        $query->where('status', $status);
    }

    // Filter for expired vouchers
    if ($expired) {
        $query->whereDate('expiry_date', '<', Carbon::now());
    }

    return $query->orderBy('created_at', 'desc')->paginate($perPage);
}



public function getVouchersByDateRange($userId, $startDate, $endDate)
{
    return Voucher::where('sponsor_id', $userId)
                  ->whereBetween('created_at', [
                      Carbon::parse($startDate)->startOfDay(),
                      Carbon::parse($endDate)->endOfDay()
                  ])
                  ->orderBy('created_at', 'desc')
                  ->get();
}



 // Get used vouchers by sponsor
public function getUsedVouchersBySponsor($sponsorId, $perPage)
{
    return Voucher::where('sponsor_id', $sponsorId)  
                  ->where('voucher_status', 'used')  
                  ->orderBy('created_at', 'desc')  
                  ->paginate($perPage);  
}


// Get used vouchers by owner
public function getUsedVouchersByOwner($ownerId, $perPage)
{
    return Voucher::where('user_id', $ownerId) 
                  ->where('voucher_status', 'used')  
                  ->orderBy('created_at', 'desc')  
                  ->paginate($perPage); 
}


// Get beneficiaries with redeemed vouchers by sponsor
public function getBeneficiariesWithRedeemedVouchersBySponsor($sponsorId, $perPage = 15)
{
    return Voucher::with('user') 
        ->where('sponsor_id', $sponsorId)
        ->where('voucher_status', 'used') 
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}

// Get beneficiaries with redeemed vouchers by owner
public function getBeneficiariesWithRedeemedVouchersByOwner($sponsorId, $perPage = 15)
{
    return Voucher::with('user') 
        ->where('user_id', $sponsorId)
        ->where('voucher_status', 'used') 
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}







}
