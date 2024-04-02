<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Referrals;
use Illuminate\Support\Facades\Auth;
use App\Referral;
use App\ReferralSetting;

class ReferralController extends Controller
{
    public $referral;

    public function __construct(Referrals $referral)
    {
        $this->referral = $referral;
    }

    public function generateReferralUrl(): \Illuminate\Http\JsonResponse
    {
        $authUser = Auth::user();

        $referralCode = $this->referral->generateReferralCode();
        $uniqueUrl = $this->referral->generateUniqueUrl($authUser->name, $referralCode);

        $mostRecent = ReferralSetting::orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        $referralData = [
            'user_id' => $authUser->id,
            'ref_code' => $referralCode,
            'ref_url' => $uniqueUrl,
            'ref_by' => $authUser->name,
            'end_date' => $mostRecent->end_date,
            'referral_duration' => $mostRecent->duration,
        ];

        $referral = $this->referral->create($referralData);

        return response()->json(['url' => $uniqueUrl]);
    }

    public function getAllReferral(): \Illuminate\Http\JsonResponse
    {
      //  return response()->json($mostRecent);
        $userId = Auth::user()->getAuthIdentifier();
        $referral = Referral::where('user_id', $userId)->get();

        return response()->json($referral);
    }
}
