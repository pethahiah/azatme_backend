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
        return "https://www.azatme.eduland.ng/register?auth={$userName}&referral_code={$referralCode}";
    }
    public function checkSettingEnquiry($modelType, $product_action): string
    {
        $referral = $this->getUserReferral();

        if (!$referral) {
            return 'No referral found for the user';
        }

        $referralSetting = ReferralSetting::where('status', 'active')->latest()->first();

        if (!$referralSetting) {
            return 'No active referral setting found';
        }

        if ($this->isReferralOngoing($referralSetting)) {
            $this->updateReferralPoint($modelType, $product_action);
            Log::info('Referral program is active.');
        }

        return 'Referral program has not started yet or has ended';
    }

    private function getUserReferral()
    {
        return Referral::where('user_id', Auth::id())->first();
    }

    private function isReferralOngoing($referralSetting): bool
    {
        return $referralSetting->duration === 'evergreen' || $this->isFixedReferralOngoing($referralSetting);
    }

    private function isFixedReferralOngoing($referralSetting): bool
    {
        $referralEndDate = Carbon::parse($referralSetting->end_date);
        $currentDate = Carbon::now();

        return $referralEndDate->greaterThanOrEqualTo($currentDate);
    }

    /**
     * @param $getUserToReward
     * @param $pointsToAward
     * @param $product_action
     * @param $modelType
     * @return void
     */
    private function addNewReferralPoints($getUserToReward, $pointsToAward, $product_action, $modelType): void
    {
        ReferralPoint::create([
            'user_id' => $getUserToReward->user_id,
            'points' => $pointsToAward,
            'product_action' => $product_action,
            'product' => $modelType,
        ]);

        Log::info('Referral points awarded successfully.');

        // Update points in the Referral table for that user
        $updatePoint = Referral::where('user_id', $getUserToReward->user_id)
            ->where('product', $modelType)
            ->first();

        if ($updatePoint) {
            $newPoint = $updatePoint->point + $pointsToAward;
            $updatePoint->update(['point' => $newPoint]);
            Log::info('Referral points updated successfully.');
        } else {
            // If no existing record, create a new one
            Referral::create([
                'user_id' => $getUserToReward->user_id,
                'product' => $modelType,
                'point' => $pointsToAward,
            ]);
            Log::info('Referral points record created successfully.');
        }
    }

    private function updateReferralPoint($modelType, $product_action): void
    {
        $userReferral = $this->getUserReferral();
        if (!$userReferral) {
            Log::warning('No user referral found.');
            return;
        }

        $refCode = $userReferral->ref_code;
        $getUserToReward = ReferralBy::where('ref_code', $refCode)->first();

        if (!$getUserToReward) {
            Log::warning('No user to reward found for referral code: ' . $refCode);
            return;
        }

        $referralSettings = ReferralSetting::whereNotNull('point_limit')->latest('created_at')->first();

        if (!$referralSettings) {
            Log::warning('No referral settings found for the specified point limit.');
            return;
        }

        // Check if the user has already been awarded points for the product and used_product
        $existingReferralPoint = ReferralPoint::where('user_id', $getUserToReward->user_id)
            ->where('product', $modelType)
            ->where('product_action', $product_action)
            ->first();

        if ($existingReferralPoint) {
            Log::info('User has already been awarded points for this product and action.');
            return;
        }

        $pointLimit = $referralSettings->referral_active_point;

        // Check if the user has reached the point limit for the referee
        $totalPoints = ReferralPoint::where('user_id', $getUserToReward->user_id)
            ->sum('points');

        if ($totalPoints >= $pointLimit) {
            Log::info('User has reached the maximum allowed points.');
            return;
        }

        // Determine points to be awarded based on referral settings
        $pointsToAward = 0;

        if ($referralSettings->product_getting_point === "A_single_product") {
            // Allow accumulation of points for distinct product actions within a single model type
            $distinctProductActions = ReferralPoint::where('user_id', $getUserToReward->user_id)
                ->where('product', $modelType)
                ->distinct('product_action')
                ->count();

            if ($distinctProductActions < 5) {
                $pointsToAward = $referralSettings->point_limit;
                $this->addNewReferralPoints($getUserToReward, $pointsToAward, $product_action, $modelType);
            } else {
                Log::info('User has already accumulated points for all distinct product actions within this model type.');
                return;
            }
        } elseif ($referralSettings->product_getting_point === "Accross_All_products") {
            // Allow accumulation of points for distinct product actions across all model types
            $distinctProductActions = ReferralPoint::where('user_id', $getUserToReward->user_id)
                ->distinct('product_action')
                ->count();

            if ($distinctProductActions < 5) {
                $pointsToAward = $referralSettings->point_limit / 5;
                $this->addNewReferralPoints($getUserToReward, $pointsToAward, $product_action, $modelType);
            } else {
                Log::info('User has already accumulated points for all distinct product actions across all model types.');
                return;
            }
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

    public function countReferralPerUser()
    {
        // Get the authenticated user
        $authenticatedUser = auth()->user();

        // If the user is authenticated
        if ($authenticatedUser) {
            // Paginate the referrals for the authenticated user
            $referrals = ReferralBy::where('user_id', $authenticatedUser->id)->paginate(10);

            // Count the total number of referrals for the authenticated user
            $referralCount = ReferralBy::where('user_id', $authenticatedUser->id)->count();

            return ['referrals' => $referrals, 'total_referrals' => $referralCount];
        }

        return null;
    }




}
