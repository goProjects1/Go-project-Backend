<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReferralSetting;
use App\Models\Trip;
use App\Models\User;
use http\Env\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use App\Mail\SendUserInviteMail;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Illuminate\Auth\AuthenticationException;

class PaymentService
{
    public function inviteUserToPayment($payment, $request)
    {
        $paymentChannel = $request->paymentChannel;
        $emails = $request->email;
        $emailsArray = explode(',', $emails);
        $emailCount = count($emailsArray);

        if ($paymentChannel === 'stripe') {
            return $this->processStripePayments($payment, $emailsArray, $request, $emailCount);
        } elseif ($paymentChannel === 'payThru') {
            return $this->processPayThruPayments($payment, $emailsArray, $request, $emailCount);
        }

        return response()->json(['message' => 'Payment channel not supported'], 400);
    }

    private function processStripePayments($payment, $emailsArray, $request, $emailCount): bool
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        foreach ($emailsArray as $key => $email) {
            try {
                $email = trim($email, '"');
                $payable = $this->calculatePayableAmount($payment, $request, $email, $emailCount, $key);
                $passenger = User::where('email', $email)->first();
                $passengerId = $passenger ? $passenger->id : null;

                $paymentRecord = Payment::create([
                    'user_id' => Auth::user()->id,
                    'passenger_id' => $passengerId,
                    'trip_id' => $payment->id,
                    'unique_code' => Str::random(10),
                    'email' => $email,
                    'split_method_id' => $request->split_method_id,
                    'reason' => $request->reason,
                    'description' => $request->description,
                    'amount' => $payable,
                ]);

                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'gbp',
                            'unit_amount' => $payable * 100,
                            'product_data' => ['name' => 'Payment for Services'],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => 'https://go-project-ashy.vercel.app/account/payment-status?result=successful',
                    'cancel_url' => 'https://go-project-ashy.vercel.app/account/payment-status?result=declined',
                ]);

                $this->sendPaymentLinkEmail($email, $session->url);
            } catch (ApiErrorException $e) {
                throw $e;
            }
        }

        return true;
    }

    private function processPayThruPayments($payment, $emailsArray, $request, $emailCount): \Illuminate\Http\JsonResponse
    {
        $token = $this->getPaythruToken();
        if (!$token) {
            return response()->json(['message' => 'Failed to retrieve PayThru token'], 500);
        }

        $payers = [];
        foreach ($emailsArray as $key => $email) {
            $payable = $this->calculatePayableAmount($payment, $request, $email, $emailCount, $key);

            Payment::create([
                'user_id' => Auth::user()->id,
                'trip_id' => $payment->id,
                'email' => $email,
                'split_method_id' => $request->split_method_id,
                'reason' => $request->reason,
                'description' => $request->description,
                'amount' => $payable,
                'bankName' => $request->bankName,
                'account_name' => $request->account_name,
                'bankCode' => $request->bankCode,
                'account_number' => $request->account_number,
            ]);

            $payers[] = ["payerEmail" => $email, "paymentAmount" => $payable];
        }

        $data = [
            'amount' => $payment->amount,
            'productId' => env('PayThru_expense_productid'),
            'transactionReference' => time() . $payment->id,
            'paymentDescription' => $request->description,
            'paymentType' => 1,
            'sign' => hash('sha512', $payment->amount . env('PayThru_App_Secret')),
            'displaySummary' => true,
        ];

        if ($emailCount > 1) {
            $data['splitPayInfo'] = ['inviteSome' => false, 'payers' => $payers];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post(env('PayThru_Base_Live_Url') . '/transaction/create', $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Failed to create PayThru transaction'], 500);
        }

        return $this->handlePayThruResponse($response, $payment, $request, $data);
    }

    private function handlePayThruResponse($response, $payment, $request, $data): \Illuminate\Http\JsonResponse
    {
        $transaction = json_decode($response->body(), true);

        if (!$transaction['successful']) {
            return response()->json(['message' => 'Whoops! ' . $transaction['message']], 400);
        }

        if (isset($data['splitPayInfo'])) {
            foreach ($transaction['splitPayResult']['result'] as $split) {
                $user = Trip::where('user_id', Auth::user()->id)->where('email', $split['receipient'])->first();
                $authUser = Auth::user();
                $userName = $user ? $user->first_name : null;

                Mail::to($split['recipient'])
                    ->send(new SendUserInviteMail($split, $authUser, $userName));

                $paylink = $split['paylink'];
                if ($paylink) {
                    $reference = last(explode('/', $paylink));
                    Payment::where(['email' => $split['receipient'], 'trip_id' => $payment->id, 'user_id' => Auth::user()->id])
                        ->update(['paymentReference' => $reference]);
                }
            }
        } else {
            $paylink = $transaction['payLink'];
            $recipient = $request->email;
            $user = Trip::where('user_id', Auth::user()->id)->where('email', $recipient)->first();
            $authUser = Auth::user();
            $userName = $user ? $user->first_name : null;

            Mail::to($recipient)
                ->send(new SendUserInviteMail(['paylink' => $paylink, 'amount' => $data['amount'], 'recipient' => $recipient], $authUser, $userName));

            if ($paylink) {
                $reference = last(explode('/', $paylink));
                Payment::where(['email' => $recipient, 'trip_id' => $payment->id, 'user_id' => Auth::user()->id])
                    ->update(['paymentReference' => $reference]);
            }
        }

        return response()->json(['message' => 'Payment processed successfully'], 200);
    }

    private function getPaythruToken()
    {
        $token = $this->paythruService->handle();

        if (!$token) {
            return false;
        }

        if (is_string($token) && strpos($token, '403') !== false) {
            return response()->json(['error' => 'Access denied. You do not have permission to access this resource.'], 403);
        }

        return $token;
    }

    private function calculatePayableAmount($payment, $request, $email, $emailCount, $key)
    {
        $payable = 0;
        $amount = $payment->amount;

        switch ($request->split_method_id) {
            case 1:
                $payable = $amount;
                break;
            case 2:
                if ($request->has('percentage')) {
                    $payable = $amount * $request->percentage / 100;
                } elseif ($request->has('percentage_per_user')) {
                    $percentages = json_decode($request->percentage_per_user, true);
                    $payable = $percentages[$email] * $amount / 100;
                }
                break;
            case 3:
                $payable = round($amount / $emailCount, 3);
                if ($key == $emailCount - 1) {
                    $payable = round($amount - ($payable * ($emailCount - 1)), 2);
                }
                break;
        }

        return $payable;
    }

    private function sendPaymentLinkEmail($email, $paymentLink)
    {
        Mail::raw("Please proceed to the following link to complete your payment: $paymentLink", function ($message) use ($email) {
            $message->to($email)->subject('Payment Link');
        });
    }

    public function getUserPaymentDetails($request)
    {
        // Check if user is authenticated before accessing user ID
        if (Auth::check()) {
            $userId = Auth::user()->getAuthIdentifier();
            return Payment::where('user_id', $userId)->paginate($request->query('per_page', 10));
        } else {
            // Throw an AuthenticationException if user is not authenticated
            throw new AuthenticationException('User is not authenticated.');
        }
    }

}
