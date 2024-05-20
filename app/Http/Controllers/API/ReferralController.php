<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends BaseController
{
    //
    public $referral;

    public function __construct(ReferralService $referral)
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

        // Correcting the return statement
        return $this->sendResponse(['url' => $uniqueUrl], 200);
    }


    public function getAllReferral(): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::user()->getAuthIdentifier();
        $referral = Referral::where('user_id', $userId)->get();
        return $this->sendResponse($referral, 200);
    }
    public function countReferralPerUsers(Request $request): \Illuminate\Http\JsonResponse
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
