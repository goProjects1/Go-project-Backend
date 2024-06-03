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
        if (!empty($authUser->first_name)) {
            $uniqueUrl = $this->referral->generateUniqueUrl($authUser->first_name, $referralCode);
        }

        if (!empty($authUser->id)) {
            if (!empty($authUser->first_name)) {
                $referralData = [
                    'user_id' => $authUser->id,
                    'ref_code' => $referralCode,
                    'ref_url' => $uniqueUrl,
                    'ref_by' => $authUser->first_name,
                ];
            }

            // Update the auth user's referral_url and hasReferral columns
            $authUser->referral_url = $uniqueUrl;
            $authUser->hasReferral = true;
            $authUser->save();
        }

        $referral = $this->referral->create($referralData);

        // Correcting the return statement
        return $this->sendResponse(['url' => $uniqueUrl], 200);
    }

    public function getReferralCode(): \Illuminate\Http\JsonResponse
    {
        $referralCode = $this->referral->getReferralCode();
        if ($referralCode) {
            return response()->json(['referral_code' => $referralCode], 200);
        }

        return response()->json(['message' => 'User has not joined referral'], 404);
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
