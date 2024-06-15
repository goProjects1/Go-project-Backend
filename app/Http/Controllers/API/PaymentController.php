<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;


class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function inviteUserToTripPayment(Request $request, $tripId)
    {

        try {
            $payment = Trip::findOrFail($tripId);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Trip not found'], 404);
        }

        if ($payment) {
            try {
                $this->paymentService->inviteUserToPayment($payment, $request);
                return response()->json(['message' => 'Payment links sent successfully']);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }

    public function getPayment(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $paymentInfo = $this->paymentService->getUserPaymentDetails($request);
            return response()->json(['message' => 'Success', 'data' => $paymentInfo], 200);
        } catch (AuthenticationException $e) {
            return response()->json(['message' => 'Authentication Error: ' . $e->getMessage()], 401);
        }
    }


}
