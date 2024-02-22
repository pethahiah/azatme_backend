<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Referrals;
use Illuminate\Support\Facades\Auth;
use App\Referral;

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

        $referralData = [
            'user_id' => $authUser->id,
            'ref_code' => $referralCode,
            'ref_url' => $uniqueUrl,
            'ref_by' => $authUser->name,
        ];

        $referral = $this->referral->create($referralData);

        return response()->json(['url' => $uniqueUrl]);
    }

    public function getAllReferral(): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::user()->getAuthIdentifier();
        $referral = Referral::where('user_id', $userId)->get();

        return response()->json($referral);
    }
}
