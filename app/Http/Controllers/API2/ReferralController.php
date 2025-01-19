<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DirectDebit;
use App\Services\Referrals;
use Illuminate\Support\Facades\Auth;
use App\Referral;
use App\ReferralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
    public $referral;
    protected $directDebitService;

    public function __construct(Referrals $referral, DirectDebit $directDebitService)
    {
        $this->referral = $referral;
        $this->directDebitService = $directDebitService;
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
        $referral = Referral::where('user_id', $userId)
	 ->orderBy('created_at', 'desc')->get();

        return response()->json($referral);
    }



public function geeetAllReferral(Request $request): \Illuminate\Http\JsonResponse
{
    // Get the number of items per page from the URL query parameter, default to 10 if not provided
    $perPage = $request->query('per_page', 10);

    // Get the page number from the URL query parameter, default to 1 if not provided
    $page = $request->query('page', 1);

    // Retrieve paginated referrals
    $referral = Referral::where('user_id', Auth::user()->id)
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    // Return paginated referrals as a JSON response
    return response()->json($referral);
}


    public function countReferralPerUser(Request $request): \Illuminate\Http\JsonResponse
    {
        // Call the method from the referral service
        $referralData = $this->referral->countReferralPerUser();
         // Check if referral data is available
        if ($referralData !== null) {
            return response()->json(['referrals' => $referralData['referrals'], 'total_referrals' => $referralData['total_referrals']], 200);
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }



}
