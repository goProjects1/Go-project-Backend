<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\Referral_By;
use App\Models\ReferralProducts;
use App\Models\ReferralSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class ReferralService
{
    public function create(array $data): Referral
    {
        return Referral::create($data);
    }

    public function generateReferralCode(): string
    {
        return Str::random(8);
    }

    public function generateUniqueUrl($userName, $referralCode): string
    {
        return "https://www.goproject.com/register?auth={$userName}&referral_code={$referralCode}";
    }

    public function checkSettingEnquiry($modelType): string
    {
        $refSetting = $this->getUserReferral();

        if (!$refSetting) {
            return 'No referral found for the user';
        }

        $referralSetting = ReferralSetting::where('status', 'active')->latest()->first();

        if (!$referralSetting) {
            return 'No active referral setting found';
        }

        if ($this->isReferralOngoing($referralSetting, $refSetting)) {
            $this->updateReferralPoint($modelType);
            return 'Referral program is active';
        }

        return 'Referral program has not started yet or has ended';
    }

    private function getUserReferral()
    {
        if (Auth::check()) {
            return Referral::where('user_id', Auth::id())->first();
        }
        return null;
    }

    public function isReferralOngoing($referralSetting): bool
    {
        return $referralSetting->duration === 'evergreen' || $this->isFixedReferralOngoing($referralSetting);
    }

    private function isFixedReferralOngoing($referralSetting): bool
    {
        if ($referralSetting) {
            $referralEndDate = Carbon::parse($referralSetting->end_date);
            $currentDate = Carbon::now();

            return $currentDate->lessThanOrEqualTo($referralEndDate);
        }
        return false;
    }

    public function updateReferralPoint($modelType)
    {
        $user = Auth::user();

        if ($user) {
            $updatePoint = Referral::where('user_id', $user->id)
                ->where('product', $modelType)
                ->first();

            $referralSettings = ReferralSetting::whereNotNull('point_limit')
                ->latest('created_at')
                ->first();

            if ($updatePoint && $referralSettings) {
                $newPoint = is_null($updatePoint->point) ? $referralSettings->point_limit : $updatePoint->point + $referralSettings->point_limit;
                $servicePoint = new ReferralProducts();
                $servicePoint->user_id = Auth::user()->getAuthIdentifier();
                $servicePoint->service = $modelType;
                $servicePoint->point = $newPoint ;
                $updatePoint->update(['point' => $newPoint]);
                Log::info('Referral points updated successfully');
                return;
            }
        }
        Log::warning('No referral found for the specified product or referral settings not found for the specified point limit');
    }

    public function processReferral($uniqueCode, $refereeEmail): array
    {
        // Fetch the referral from the referral table using the unique code
        $referral = Referral::where('ref_code', $uniqueCode)->first();

        // Check if the referral exists
        if ($referral) {
            // Save user details in the referral_by table
            Referral_By::create([
                'user_id' => $referral->user_id,
                'ref_code' => $uniqueCode,
                'referee_email' => $refereeEmail,
            ]);

            return ['success' => true, 'message' => 'Referral processed successfully'];
        } else {
            return ['success' => false, 'message' => 'Referral not found'];
        }
    }

    public function countReferralPerUser(): ?array
    {
        // Get the authenticated user
        $authenticatedUser = auth()->user();

        // If the user is authenticated
        if ($authenticatedUser) {
            // Paginate the referrals for the authenticated user
            $referrals = Referral_By::where('user_id', $authenticatedUser->id)->paginate(10);

            // Count the total number of referrals for the authenticated user
            $referralCount = Referral_By::where('user_id', $authenticatedUser->id)->count();

            return ['referrals' => $referrals, 'total_referrals' => $referralCount];
        }

        return null;
    }


}
