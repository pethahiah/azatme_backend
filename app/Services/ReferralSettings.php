<?php

namespace App\Services;

use App\ReferralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReferralSettings
{
    public function createReferral($requestData)
    {

        $startDate = now();


        $endDate = Carbon::createFromFormat('d/m/Y', $requestData['end_date']);


        $duration = ($requestData['duration'] === 'evergreen') ? 1200 : $requestData['duration'];

        return ReferralSetting::create([
            'admin_id' => Auth::user()->getAuthIdentifier(),
            'duration' => $duration,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'point_conversion' => $requestData['point_conversion'] ?? null,
            'point_limit' => $requestData['point_limit'] ?? null,
            'status' => $requestData['status'] ?? null,
        ]);
    }

    public function updateReferral($referralId, $requestData)
    {
        $startDate = now();

        $endDate = Carbon::createFromFormat('d/m/Y', $requestData['end_date']);


        $duration = ($requestData['duration'] === 'evergreen') ? 1200 : $requestData['duration'];

        $updateData = [
            'duration' => $duration,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'point_conversion' => $requestData['point_conversion'] ?? null,
            'point_limit' => $requestData['point_limit'] ?? null,
            'status' => $requestData['status'] ?? null,
        ];

        ReferralSetting::where('id', $referralId)->update($updateData);

        return ReferralSetting::find($referralId);
    }
}
