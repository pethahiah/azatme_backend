<?php

namespace App\Services;

use App\ReferralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralSettings
{
    
public function createReferral(Request $request, $requestData)
{
    $startDate = isset($requestData['start_date']) ? Carbon::createFromFormat('Y-m-d', $requestData['start_date']) : Carbon::now();

   
    $endDate = ($request->input('duration') === 'evergreen') ? null : (isset($requestData['end_date']) ? Carbon::createFromFormat('Y-m-d', $requestData['end_date']) : null);

   
    $validDurations = ['evergreen', 'fixed'];
    $duration = in_array($request->input('duration') ?? 'fixed', $validDurations) ? ($request->input('duration') ?? 'fixed') : 'fixed';

    return ReferralSetting::create([
        'admin_id' => Auth::user()->getAuthIdentifier(),
        'duration' => $duration,
        'start_date' => $startDate->format('Y-m-d'), 
        'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
        'point_conversion' => $requestData['point_conversion'] ?? null,
        'point_limit' => $requestData['point_limit'] ?? null,
        'status' => $requestData['status'] ?? null,
    ]);
}

   public function updateReferral(Request $request, $referralId, $requestData)
    {
        $startDate = isset($requestData['start_date']) ? Carbon::createFromFormat('Y-m-d', $requestData['start_date']) : Carbon::now();

        $endDate = ($request->input('duration') === 'evergreen') ? null : (isset($requestData['end_date']) ? Carbon::createFromFormat('Y-m-d', $requestData['end_date']) : null);

        $validDurations = ['evergreen', 'fixed'];
        $duration = in_array($request->input('duration') ?? 'fixed', $validDurations) ? ($request->input('duration') ?? 'fixed') : 'fixed';

        $updateData = [
            'admin_id' => Auth::user()->getAuthIdentifier(),
            'duration' => $duration,
            'start_date' => $startDate->format('Y-m-d'), 
            'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
            'point_conversion' => $requestData['point_conversion'] ?? null,
            'point_limit' => $requestData['point_limit'] ?? null,
            'status' => $requestData['status'] ?? null,
        ];

        ReferralSetting::where('id', $referralId)->update($updateData);

        return ReferralSetting::find($referralId);
    }
}
