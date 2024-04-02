<?php

namespace App\Services;

use App\ReferralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReferralSettings
{
    public function createReferral($requestData)
    {
        $startDate = isset($requestData['start_date']) ? Carbon::createFromFormat('d/m/Y', $requestData['start_date']) : Carbon::now();
        $endDate = Carbon::createFromFormat('d/m/Y', $requestData['end_date']);

        // Ensure valid duration
        $validDurations = ['evergreen', 'fixed'];
        $duration = in_array($requestData['duration'] ?? 'evergreen', $validDurations) ? ($requestData['duration'] ?? 'evergreen') : 'evergreen';

        return ReferralSetting::create([
            'admin_id' => Auth::user()->getAuthIdentifier(),
            'duration' => $duration,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'point_conversion' => $requestData['point_conversion'] ?? null,
            'point_limit' => $requestData['point_limit'] ?? null,
            'status' => $requestData['status'] ?? null,
        ]);
    }



    public function updateReferral($referralId, $requestData)
    {
        $startDate = now();

        $endDate = Carbon::createFromFormat('d/m/Y', $requestData['end_date']);

        $duration = $requestData['duration'] ?? 'evergreen';
        $duration = ($duration === 'evergreen' || $duration === 'fixed') ? $duration : 'evergreen';

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
