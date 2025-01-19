<?php

namespace App\Services;


use App\Referral;
use App\ReferralBy;
use App\User;
use App\ReferralSetting;
use App\ReferralPoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\AjoWithdrawal;
use App\GroupWithdrawal;
use App\Withdrawal;
use App\BusinessWithdrawal;


class Referrals
{
    public function create(array $data): Referral
    {
        return Referral::create($data);
    }

    public function generateReferralCode(): string
    {
        return Str::random(8);
    }

    public function generateUniqueUrl($userName, $referralCode): string
    {
//        return "https://www.azatme.eduland.ng/register?auth={$userName}&referral_code={$referralCode}";
	return "https://www.azatme.com/register?auth={$userName}&referral_code={$referralCode}";

    }



public function checkSettingEnquiry($modelType, $product_action, $transactionReferences): string
{
    Log::info("Checking setting enquiry for modelType: $modelType and product_action: $product_action");

    $referral = $this->getUserReferral($transactionReferences);

    if (!$referral) {
        Log::warning('No referral found for the current user.');
        return 'No referral found for the user';
    }

    Log::info('User referral found with ref_code: ' . $referral->ref_code);

    $referralSetting = ReferralSetting::where('status', 'active')->latest()->first();

    if (!$referralSetting) {
        Log::warning('No active referral setting found.');
        return 'No active referral setting found';
    }

    Log::info('Active referral setting found with ID: ' . $referralSetting->id);

    if ($this->isReferralOngoing($referralSetting)) {
        Log::info('Referral program is active.');
        $this->updateReferralPoint($modelType, $product_action, $transactionReferences);
    } else {
        Log::info('Referral program has not started yet or has ended.');
    }

    return 'Referral program has not started yet or has ended';
}


private function getUserReferral($transactionReferences)
{
    // Check which withdrawal table contains the transaction reference
    $referralRecord = null;

    $withdrawalSources = [
        'Kontribute' => GroupWithdrawal::class,
        'RefundMe' => Withdrawal::class,
        'Ajo' => AjoWithdrawal::class,
        'Business' => BusinessWithdrawal::class
    ];

    foreach ($withdrawalSources as $source => $model) {
        if ($source === 'Ajo') {
            $withdrawal = $model::where('transactionReference', $transactionReferences)->first();
        } else {
            $withdrawal = $model::where('transactionReferences', $transactionReferences)->first();
        }

        if ($withdrawal) {
            $beneficiaryId = $withdrawal->beneficiary_id;
            $userEmail = User::where('id', $beneficiaryId)->value('email');

            Log::info("Found transaction in $source table, with user email: $userEmail");

            $referralRecord = ReferralBy::where('referee_email', $userEmail)->first();

            if ($referralRecord) {
                break;  // Exit the loop once a referral record is found
            }
        }
    }

    if (!$referralRecord) {
        Log::warning('No referral record found for the current user.');
        return null;
    }

    Log::info('Referral record found', ['referralRecord' => $referralRecord]);

    return $referralRecord;
}


private function isReferralOngoing($referralSetting): bool
{
    Log::info('Checking if referral is ongoing for setting ID: ' . $referralSetting->id);
    return $referralSetting->duration === 'evergreen' || $this->isFixedReferralOngoing($referralSetting);
}

private function isFixedReferralOngoing($referralSetting): bool
{
    $referralEndDate = Carbon::parse($referralSetting->end_date);
    $currentDate = Carbon::now();

    Log::info('Checking if fixed referral is ongoing. End date: ' . $referralEndDate->toDateTimeString() . ', Current date: ' . $currentDate->toDateTimeString());

    return $referralEndDate->greaterThanOrEqualTo($currentDate);
}


private function updateReferralPoint($modelType, $product_action, $transactionReferences): void 
{
    // Retrieve the referral record based on the transaction references
    $referralRecord = $this->getUserReferral($transactionReferences);
    if (!$referralRecord) {
        Log::warning('No user referral found for transaction references.', ['transactionReferences' => $transactionReferences]);
        return;
    }

    // Get the referrer user ID and ref code
    $referrerUserId = $referralRecord->user_id;
    $refCode = $referralRecord->ref_code;

    // Log referrer information
    Log::info("Referring user found", ['referrer_user_id' => $referrerUserId, 'ref_code' => $refCode]);

    // Fetch referral settings
    $referralSettings = ReferralSetting::whereNotNull('point_limit')->latest('created_at')->first();
    if (!$referralSettings) {
        Log::warning('No referral settings found for the specified point limit.');
        return;
    }

    // Check if a referral point record for the user exists
    $existingReferralPoint = ReferralPoint::where('user_id', $referrerUserId)->first();

    // If no referral point exists, create a new one for the user
    if (!$existingReferralPoint) {
        Log::info('No existing referral points found for user, creating new referral point.', [
            'referrer_user_id' => $referrerUserId
        ]);

        // Proceed with awarding points based on referral settings
        $this->awardPointsBasedOnSettings($referralSettings, $referrerUserId, $product_action, $modelType);
        return;
    }

    // If referral points exist, calculate the total points
    $totalPoints = ReferralPoint::where('user_id', $referrerUserId)->sum('point');

    // Log the total points and point limit
    Log::info('Total points for referrer user', [
        'referrer_user_id' => $referrerUserId,
        'total_points' => $totalPoints,
        'point_limit' => $referralSettings->referral_active_point
    ]);

    $pointLimit = $referralSettings->referral_active_point;

    // Check if the referrer has reached the point limit
    if ($totalPoints >= $pointLimit) {
        Log::info('User has reached the maximum allowed points.', [
            'referrer_user_id' => $referrerUserId,
            'total_points' => $totalPoints,
            'point_limit' => $pointLimit
        ]);
        return;
    }

    // Proceed with awarding points based on referral settings
    $this->awardPointsBasedOnSettings($referralSettings, $referrerUserId, $product_action, $modelType);
}



private function awardPointsBasedOnSettings($referralSettings, $referrerUserId, $product_action, $modelType): void
{
    // Determine points to be awarded based on referral settings
    $pointsToAward = 0;
    $pointLimit = $referralSettings->referral_active_point;

    // Ensure referral points exist for the user before proceeding
    $hasReferralPoints = ReferralPoint::where('user_id', $referrerUserId)->exists();

    if (!$hasReferralPoints) {
        // Create referral points if none exist
        $this->addNewReferralPoints($referrerUserId, $pointsToAward, $product_action, $modelType);
        return;
    }

    // Proceed with distinct product actions logic
    if ($referralSettings->product_getting_point === "A_single_product") {
        // Allow accumulation of points for distinct product actions within a single model type
        $distinctProductActions = ReferralPoint::where('user_id', $referrerUserId)
            ->where('product', $modelType)
            ->distinct('product_action')
            ->count();

        Log::info('Distinct product actions for a single product type', [
            'referrer_user_id' => $referrerUserId,
            'distinct_product_actions' => $distinctProductActions,
            'point_limit' => $pointLimit
        ]);

        if ($distinctProductActions < $pointLimit) {
            $pointsToAward = $referralSettings->point_limit;
            Log::info('Awarding points for a single product: ' . $pointsToAward);
            $this->addNewReferralPoints($referrerUserId, $pointsToAward, $product_action, $modelType);
        } elseif ($distinctProductActions === $pointLimit) {
            // Convert points to amount
            $this->convertPointsToAmount($referrerUserId, $distinctProductActions, $referralSettings);
        } else {
            Log::info('User has already accumulated points for all distinct product actions within this model type.');
            return;
        }
    } elseif ($referralSettings->product_getting_point === "Accross_All_products") {
        // Allow accumulation of points for distinct product actions across all model types
        $distinctProductActions = ReferralPoint::where('user_id', $referrerUserId)
            ->distinct('product_action')
            ->count();

        Log::info('Distinct product actions across all product types', [
            'referrer_user_id' => $referrerUserId,
            'distinct_product_actions' => $distinctProductActions,
            'point_limit' => $pointLimit
        ]);

        if ($distinctProductActions < $pointLimit) {
            $pointsToAward = $referralSettings->point_limit;
            Log::info('Awarding points across all products: ' . $pointsToAward);
            $this->addNewReferralPoints($referrerUserId, $pointsToAward, $product_action, $modelType);
        } elseif ($distinctProductActions === $pointLimit) {
            // Convert points to amount
            $this->convertPointsToAmount($referrerUserId, $distinctProductActions, $referralSettings);
        } else {
            Log::info('User has already accumulated points for all distinct product actions across all model types.');
            return;
        }
    } else {
        Log::warning('Unexpected referral setting: ' . $referralSettings->product_getting_point);
    }
}



private function convertPointsToAmount($referrerUserId, $distinctProductActions, $referralSettings): void
{
    // Log the conversion attempt
    Log::info('Converting points to amount for user ID: ' . $referrerUserId, [
        'aggregate_point' => $distinctProductActions,
        'amount_conversion' => $referralSettings->amount_conversion
    ]);

    // Create a new referral point conversion entry
    ReferralPointConversion::create([
        'user_id' => $referrerUserId,
        'aggregate_point' => $distinctProductActions,
        'amount_conversion' => $referralSettings->amount_conversion,
    ]);

    // Log successful conversion
    Log::info('Points converted to amount successfully for user ID: ' . $referrerUserId, [
        'aggregate_point' => $distinctProductActions,
        'amount_conversion' => $referralSettings->amount_conversion
    ]);
}





private function addNewReferralPoints($referrerUserId, $pointsToAward, $product_action, $modelType): void
{
    // Log the attempt to add new referral points
    Log::info('Adding new referral points for user ID: ' . $referrerUserId . '. Points to award: ' . $pointsToAward);

    // Create a new referral point entry
    ReferralPoint::create([
        'user_id' => $referrerUserId,
        'points' => $pointsToAward,
        'product_action' => $product_action,
        'product' => $modelType,
    ]);

    // Log successful addition of referral points
    Log::info('Referral points awarded successfully for user ID: ' . $referrerUserId, [
        'points_awarded' => $pointsToAward,
        'product_action' => $product_action,
        'product' => $modelType,
    ]);

    // Update points in the Referral table for that user
    $updatePoint = Referral::where('user_id', $referrerUserId)->first();
    if ($updatePoint) {
        $newPoint = $updatePoint->point + $pointsToAward;
        $updatePoint->update(['point' => $newPoint]);

        Log::info('Referral points updated successfully for user ID: ' . $referrerUserId . '. New point total: ' . $newPoint);
    } else {
        Log::warning('Referral record not found for user ID: ' . $referrerUserId);
    }
}







    public function processReferral($uniqueCode, $refereeName, $refereeEmail): array
    {
        // Fetch the referral from the referral table using the unique code
        $referral = Referral::where('ref_code', $uniqueCode)->first();

        // Check if the referral exists
        if ($referral) {
            // Save user details in the referral_by table
            ReferralBy::create([
                'user_id' => $referral->user_id,
                'ref_code' => $uniqueCode,
                'referee_name' => $refereeName,
                'referee_email' => $refereeEmail,
            ]);

            return ['success' => true, 'message' => 'Referral processed successfully'];
        } else {
            return ['success' => false, 'message' => 'Referral not found'];
        }
    }

public function countReferralPerUser(): ?array
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser) {
            $referrals = ReferralBy::where('user_id', $authenticatedUser->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $referralCount = ReferralBy::where('user_id', $authenticatedUser->id)->count();

            return ['referrals' => $referrals, 'total_referrals' => $referralCount];
        }

        return null;
    }



}
