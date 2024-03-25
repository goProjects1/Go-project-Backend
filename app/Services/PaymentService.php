<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class PaymentService
{
    public function inviteUserToPayment(Trip $payment, $emails, $request): bool
    {
        $userPayments = explode(',', $emails);
        $count = count($userPayments);
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        foreach ($userPayments as $key => $em) {
            try {
                $em = trim($em, '"');
                $payable = null;

                // Calculate payable amount based on split method
                $payable = $this->calculatePayableAmount($payment, $request, $em, $count, $key, $payable);
                $passengerId  = User::where('email', $em)->first();

                // Create payment record
                $info = Payment::create([
                    'user_id' => Auth::user()->id,
                    'passenger_id' => $passengerId ? $passengerId->id : null,
                    'trip_id' => $payment->id,
                    'unique_code' => Str::random(10),
                    'email' => $em,
                    'split_method_id' => $request->split_method_id,
                    'amount' => $payable,
                ]);

                // Create a new Stripe Checkout session
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'gbp',
                            'unit_amount' => $payable * 100,
                            'product_data' => [
                                'name' => 'Payment for Services',
                            ],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => 'https://example.com/success',
                    'cancel_url' => 'https://example.com/cancel',
                ]);

                // Send the Payment Link to the user via email
                $this->sendPaymentLinkEmail($em, $session->url);
            } catch (ApiErrorException $e) {
                // Handle any errors
                throw $e;
            }
        }

        return true;
    }


    private function calculatePayableAmount($payment, $request, $em, $count, $key, $payable)
    {
        if ($request->split_method_id == 1) {
            $payable = $payment->fee_amount;
        } elseif ($request->split_method_id == 2) {
            if ($request->has('percentage')) {
                $payable = $payment->fee_amount * $request->percentage / 100;
            } elseif ($request->has('percentage_per_user')) {
                $ppu = json_decode($request->percentage_per_user);
                $payable = $ppu->$em * $payment->fee_amount / 100;
            }
        } elseif ($request->split_method_id == 3) {
            $payable = round($payment->fee_amount / $count, 3);
            if ($key == $count - 1) {
                $payable = round($payment->fee_amount - ($payable * ($count - 1)), 2);
            }
        }
        return $payable;
    }

    private function sendPaymentLinkEmail($em, $paymentLink)
    {
        // Send email with payment link
        Mail::raw("Please proceed to the following link to complete your payment: $paymentLink", function ($message) use ($em) {
            $message->to($em)
                ->subject('Payment Link');
        });
    }
}
