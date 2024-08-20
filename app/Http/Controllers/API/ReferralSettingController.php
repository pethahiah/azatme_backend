<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ReferralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class ReferralSettingController extends Controller
{
    //
    public $referralSetting;

    public function __construct(ReferralSettings $referralSetting)
    {
        $this->referralSetting = $referralSetting;
    }

    public function createReferral(Request $request): \Illuminate\Http\JsonResponse
    {
        $requestData = $request->all();
         Log::info("request", ['data' => $requestData, 'modelType' => $requestData]);
    
        $referralSetting = $this->referralSetting->createReferral($request, $requestData);
        return response()->json($referralSetting, 201);
    }


    public function updateReferral(Request $request, $referralId): \Illuminate\Http\JsonResponse
    {
        $requestData = $request->all();
        $updatedReferral = $this->referralSetting->updateReferral($request, $referralId, $requestData);
        return response()->json($updatedReferral);
    }

    public function getAllReferralSettings(): \Illuminate\Http\JsonResponse
    {
        $adminId = Auth::user()->getAuthIdentifier();

        $referralSettings = ReferralSetting::where('admin_id', $adminId)->get();

        return response()->json($referralSettings);
    }
}
