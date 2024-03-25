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
        return "https://www.azatme.eduland.ng/register?auth={$userName}&referral_code={$referralCode}";
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

    public function isReferralOngoing($referralSetting, $refSetting): bool
    {
        return $refSetting->duration === 'evergreen' || $this->isFixedReferralOngoing($referralSetting);
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

    public function processReferral($url)
    {

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            // Check if the required parameters are present
            if (isset($queryParams['userName']) && isset($queryParams['ref_code'])) {
                $userName = $queryParams['userName'];
                $refCode = $queryParams['ref_code'];

                // Fetch the user from the user table
                $user = User::where('email', $userName)->first();

                // Check if the user exists
                if ($user) {
                    // Save user details in the referral_by table
                    Referral_By::create([
                        'user_id' => $user->id,
                        'ref_code' => $refCode,
                    ]);

                    return ['success' => true, 'message' => 'User details saved successfully'];
                } else {
                    return ['success' => false, 'error' => 'User not found'];
                }
            } else {
                return ['success' => false, 'error' => 'Invalid URL parameters'];
            }
        } else {
            return ['success' => false, 'error' => 'No query parameters found in the URL'];
        }
    }
}
