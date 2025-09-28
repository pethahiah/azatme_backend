<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\VoucherPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Sponsor;
use App\User;
use App\Services\PaythruService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\SponsorWallet;
use App\SponsorLimit;
use Mail;
use App\Mail\UserBlockedNotification;
use App\Mail\FundLimitExceeded;


class SponsorService
{

    protected $paythruService;

    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }
    



 // Get all vouchers or a specific voucher by sponsor
    public function getVouchersBySponsor(int $sponsorId, int $perPage = 15, $voucherId = null)
{
    if ($voucherId) {
        Log::info('Fetching single voucher by sponsor', [
            'sponsor_id' => $sponsorId,
            'voucher_id' => $voucherId,
        ]);

        return Voucher::where('sponsor_id', $sponsorId)
            ->where('id', $voucherId)
            ->first();
    } else {
        Log::info('Fetching all vouchers by sponsor with pagination', [
            'sponsor_id' => $sponsorId,
            'per_page' => $perPage,
        ]);

        return Voucher::where('sponsor_id', $sponsorId)
            ->paginate($perPage);
    }
}

public function getBySponsor(Request $request): JsonResponse
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




public function getUserWalletBalance($userId)
    {
        $wallet = SponsorWallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return [
                'status' => 'error',
                'message' => 'Wallet not found for this user.',
            ];
        }

        return [
            'status' => 'success',
            'data' => [
                'user_id' => $wallet->user_id,
                'wallet_balance' => $wallet->wallet_balance,
                'updated_at' => $wallet->updated_at,
            ],
        ];
    }


public function checkFundLimit(User $user, $amount)
    {
	
        // Retrieve the sponsor's fund limit
        $fundLimit = SponsorLimit::latest()->value('fund_limit');
	
        if ($amount > $fundLimit) {
            // Increment the number of failed attempts
            $user->sec_flag += 1;

            // Send email notification to the user using the FundLimitExceeded mailable
            Mail::to($user->email)->send(new FundLimitExceeded($user, $amount, $fundLimit));

            // If the user has reached 3 attempts, block the user and notify admin
            if ($user->sec_flag >= 3) {
                $user->flag = true; // Block the user

                // Save the user update before sending admin email
                $user->save();

                // Send email to the platform admin notifying that the user has been blocked
                Mail::to('support@pethahiah.com')->send(new UserBlockedNotification($user, $amount, $fundLimit));

                return [
                    'success' => false,
                    'message' => 'User has been blocked due to exceeding the fund limit three times.'
                ];
            }

            $user->save();

            return [
                'success' => false,
                'message' => "Amount exceeds fund limit. Attempt: {$user->sec_flag}"
            ];
        }

        return [
            'success' => true,
            'message' => 'Amount is within the fund limit.'
        ];
    }



 public function createSponsor($data)
    {
        // Create the sponsor account
        $sponsor = Sponsor::create($data);

        return ['success' => true, 'message' => 'Sponsor account created successfully', 'data' => $sponsor];
    }


 // Get all sponsor accounts for a given user
    public function getAllSponsorsByUserId($userId)
    {
        return Sponsor::where('user_id', $userId)->get();
    }

    // Get a single sponsor account by its ID
    public function getSponsorById($sponsorId)
    {
        return Sponsor::find($sponsorId);
    }

   public function getSponsorDetails($sponsorId, array $userProfile): array
        {
            // Find the sponsor by ID and load associated user details
            $sponsor = Sponsor::findOrFail($sponsorId);
            return [
                'sponsor' => $sponsor->toArray(),
                'user' => $userProfile,
            ];
        }


public function updateSponsor($sponsorId, $data)
{
    $sponsor = Sponsor::findOrFail($sponsorId);

    // Log data before updating
    \Log::info('Updating sponsor with data:', $data);

    // Update the sponsor details
    $sponsor->update($data);

    return $sponsor->fresh();
}

    // Delete a sponsor account
    public function deleteSponsor($sponsorId)
    {
        $sponsor = Sponsor::find($sponsorId);

        if (!$sponsor) {
            throw new ModelNotFoundException('Sponsor not found.');
        }

        // Soft delete the sponsor account (if you need a hard delete, use $sponsor->delete())
        $sponsor->delete();

        return true;
    }



public function fundSponsorsWallet($amount, $sponsor_id, $paymentGateway)
{
    try {
        $description = "Sponsor wallet funding";
        $paymentReference = 'TXN_' . time();

        if ($paymentGateway === 'paythru') {
            // Use PayThru payment integration
            $prodUrl = env('PayThru_Base_Live_Url');
            $productId = env('PayThru_expense_productid');
            $secret = env('PayThru_App_Secret');
            $hashSign = hash('sha512', $amount . $secret);
            $token = $this->paythruService->handle();

            $data = [
                'amount' => $amount,
                'productId' => $productId,
                'transactionReference' => $paymentReference,
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
                return ['status' => 'error', 'message' => 'Transaction failed.'];
            }

            $transaction = json_decode($response->body(), true);
            if (!$transaction['successful']) {
                return ['status' => 'error', 'message' => 'Whoops! ' . $transaction['message']];
            }

            $paylink = $transaction['payLink'];
        } else {
            // Use Paystack payment integration (Webhook-based)
            $paystackSecret = env('PAYSTACK_SECRET_KEY');

            $data = [
                'email' => Auth::user()->email,
                'amount' => $amount * 100,
                'currency' => 'NGN',  'reference' => $paymentReference,
                'metadata' => [
                    'sponsor_id' => $sponsor_id,
                    'user_id' => $sponsor_id,
                    'description' => $description,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecret,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $data);

            if ($response->failed()) {
                return ['status' => 'error', 'message' => 'Transaction initialization failed.'];
            }

            $transaction = json_decode($response->body(), true);
            if (!$transaction['status']) {
                return ['status' => 'error', 'message' => 'Whoops! ' . $transaction['message']];
            }

            $paylink = $transaction['data']['authorization_url'];
        }

        // Save transaction as "pending" in database
        $payment = VoucherPayment::create([
            'amount' => $amount,
            'sponsor_id' => $sponsor_id, // Ensure sponsor_id is passed here
            'description' => $description,
            'paymentReference' => $paymentReference,
            'paymentMethod' => $paymentGateway,
            'status' => 'pending',
        ]);


	$sponsorWallet = SponsorWallet::updateOrCreate(
    ['user_id' => $sponsor_id],
    ['reference' => $paymentReference]
);

        $sponsorExists = Sponsor::where('user_id', $sponsor_id)->exists();
        $userExists = User::where('id', $sponsor_id)->exists();

if ($sponsorExists || $userExists){
            return [
                'status' => 'success',
                'data' => [
                    'transaction_amount' => $amount,
                    'created_at' => $payment->created_at,
                    'pay_link' => $paylink,
                ],
            ];
        } else {
            return ['status' => 'error', 'message' => 'Sponsor not found.'];
        }

    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return ['status' => 'error', 'message' => 'Unexpected error occurred.'];
    }
}


}
