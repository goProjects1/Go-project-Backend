<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class MposService
{

    public $paythruService;


    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;

    }

    private function generateUniqueCode(): string
    {
        // Generate a unique code for the transaction
        return uniqid();
    }
    public function mPosOneTimePay($payment, $email, $request): JsonResponse
    {
        $currentTimestamp = now();
        $prodUrl = env('PayThru_Base_Live_Url');
        $amount = $request->input('amount');
        $productId = env('PayThru_business_productid');
        $timestamp = strtotime($currentTimestamp);
        $secret = env('PayThru_App_Secret');

        $hashSign = hash('sha512', $amount . $secret);
        $token = $this->paythruService->handle();
       // $description = "Mpos payment option";

        $data = [
            'amount' => $amount,
            'productId' => $productId,
            'transactionReference' => time() . $amount,
            'paymentDescription' => $payment->description,
            'paymentType' => 1,
            'sign' => $hashSign,
            'displaySummary' => false,
        ];

        $url = $prodUrl . '/transaction/create';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Transaction failed.'], 400);
        }

        $transaction = json_decode($response->body(), true);

        if (!$transaction['successful']) {
            return response()->json(['message' => 'Whoops! ' . json_encode($transaction['message'])], 400);
        }

        $paylink = $transaction['payLink'];

        if ($paylink) {
            $getLastString = explode('/', $paylink);
            $now = end($getLastString);

            $paymentRecord = Payment::create([
                'user_id' => Auth::user()->id,
                'trip_id' => $payment->id,
                'unique_code' => $this->generateUniqueCode(),
                'email' => $email,
                'reason' => $request->reason,
                'description' => $payment->description,
                'amount' => $amount,
                'paymentReference' => $now,
            ]);

            return response()->json($transaction);
        }

        return response()->json(['message' => 'Unexpected error occurred.'], 500);
    }
}
