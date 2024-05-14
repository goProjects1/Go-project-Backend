<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\ReferralSetting;
use App\Services\ReferralSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralSettingController extends BaseController
{
    //
    public $referralSetting;

    public function __construct(ReferralSettingService $referralSetting)
    {
        $this->referralSetting = $referralSetting;
    }

    public function createReferral(Request $request): \Illuminate\Http\JsonResponse
    {
        $createRef = $this->referralSetting->createReferral($request->all());
        return response()->json($createRef);
    }



    public function updateReferral(Request $request, $referralId): \Illuminate\Http\JsonResponse
    {
        $updatedReferral = $this->referralSetting->updateReferral($referralId, $request->all());

        return response()->json($updatedReferral);
    }


    public function getAllReferralSettings(): \Illuminate\Http\JsonResponse
    {
        $adminId = Auth::user()->getAuthIdentifier();

        $referralSettings = ReferralSetting::where('admin_id', $adminId)->get();

        return response()->json($referralSettings);
    }
}
