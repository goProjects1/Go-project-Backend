<?php

namespace App\Services;

use App\Models\ReferralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralSettingService
{
    public function createReferral($requestData)
    {
        $request = request();

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


    public function updateReferral($referralId, $requestData)
    {
        $request = request(); // Get the current request instance

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

    public function getAllReferralSettings($perPage = 10)
    {
        return ReferralSetting::paginate($perPage);
    }

    public function getReferralSettingById($id)
    {
        return ReferralSetting::find($id);
    }

}
