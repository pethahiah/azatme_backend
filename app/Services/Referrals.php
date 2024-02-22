<?php

namespace App\Services;


use App\Referral;
use App\ReferralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;



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

        if ($this->isReferralOngoing($referral)) {
            $this->updateReferralPoint($modelType);
            return 'Referral program is active';
        }

        return 'Referral program has not started yet or has ended';
    }

    private function getUserReferral()
    {
        return Referral::where('user_id', Auth::id())->first();
    }

    private function isReferralOngoing($referral)
    {
        return $referral->duration === 'evergreen' || $this->isFixedReferralOngoing($referral);
    }

    private function isFixedReferralOngoing($referral)
    {
        $referralEndDate = Carbon::parse($referral->end_date);
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

            return response()->json(['message' => 'Referral points updated successfully']);
        }

        return response()->json(['message' => 'No referral found for the specified product or referral settings not found for the specified point limit']);
    }


}
