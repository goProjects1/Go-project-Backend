<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function inviteUserToTripPayment(Request $request, $tripid)
    {

        try {
            $payment = Trip::findOrFail($tripid);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Trip not found'], 404);
        }

       $emails = $request->email;

        if ($emails) {
            try {
                $this->paymentService->inviteUserToPayment($payment, $emails, $request);
                return response()->json(['message' => 'Payment links sent successfully']);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }

}
