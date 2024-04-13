<?php

namespace App\Services;


use App\Referral;
use App\ReferralBy;
use App\User;
use App\ReferralSetting;
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
    public function checkSettingEnquiry($modelType): string
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
            $this->updateReferralPoint($modelType);
            return 'Referral program is active';
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

    private function isFixedReferralOngoing($referralSetting)
    {
        $referralEndDate = Carbon::parse($referralSetting->end_date);
        $currentDate = Carbon::now();

        return $referralEndDate->lessThanOrEqualTo($currentDate)
            ? true
            : 'Referral program has ended';
    }

    private function updateReferralPoint($modelType)
    {
        $user = Auth::user();
        $updatePoint = Referral::where('user_id', $user->id)
            ->where('product', $modelType)
            ->first();

        $referralSettings = ReferralSetting::whereNotNull('point_limit')
            ->latest('created_at')
            ->first();

        if ($updatePoint && $referralSettings) {
            $newPoint = is_null($updatePoint->point) ? $referralSettings->point_limit : $updatePoint->point + $referralSettings->point_limit;
            $updatePoint->update(['point' => $newPoint]);
            Log::info('Referral points updated successfully');
        }
        Log::warning('No referral found for the specified product or referral settings not found for the specified point limit');
    }


     public function processReferral($url): array
     {

        $parsedUrl = parse_url($url);


        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            // Check if the required parameters are present
            if (isset($queryParams['userName']) && isset($queryParams['ref_code'])) {
                $userName = $queryParams['userName'];
                $refCode = $queryParams['ref_code'];

                // Fetch the user from the user table
                $user = User::where('name', $userName)->first();

                // Check if the user exists
                if ($user) {
                    // Save user details in the referral_by table
                    ReferralBy::create([
                        'user_id' => $user->id,
                        'ref_code' => $refCode,
                    ]);

                    return ['success' => true, 'message' => 'User details saved successfully'];
                } else {
                    return ['success' => false, 'error' => 'User not found'];
                }
            } else {
                return ['success' => false, 'error' => 'Invalid URL parameters'];
            }
        } else {
            return ['success' => false, 'error' => 'No query parameters found in the URL'];
        }
    }


}
