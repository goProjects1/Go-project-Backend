<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Active;
use App\Models\Bank;
use App\Models\Payment;
use App\Models\ReferralSetting;
use App\Models\Trip;
use App\Models\User;
use App\Models\Withrawal;
use App\Services\MposService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class PaymentController extends BaseController
{
    public $paymentService;
    public $mposService;
    public function __construct(PaymentService $paymentService, MposService $mposService)
    {
        $this->paymentService = $paymentService;
        $this->mposService = $mposService;
    }

    public function inviteUserToTripPayment(Request $request, $tripId)
    {

       // return $payment = Trip::findOrFail($tripId);
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


    public function mPosOneTimePay(Request $request, $tripId)
    {

        // return $payment = Trip::findOrFail($tripId);
        try {
            $payment = Trip::findOrFail($tripId);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Trip not found'], 404);
        }
        $email = $request->email;
        if ($payment) {
            try {
                $transaction = $this->mposService->mPosOneTimePay($payment, $email,$request);
                return $this->sendResponse($transaction,  'Payment links sent successfully');

            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }


    // Calling PayThru gateway for transaction response updates
    public function webhookExpenseResponse(\http\Env\Request $request)
    {
        try {
            $productId = env('PayThru_expense_productid');
            $response = $request->all();
            $dataEncode = json_encode($response);
            $data = json_decode($dataEncode);
            $modelType = "RefundMe";


            Log::info("Starting webhookExpenseResponse", ['data' => $data, 'modelType' => $modelType]);
            Log::info("Starting webhookExpenseResponse");

            if ($data->notificationType == 1) {
                $userExpense = Payment::where('paymentReference', $data->transactionDetails->paymentReference)->first();
//	    $minus_residual  =  $userExpense->minus_residual;

                $product_action = "payment";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType, $product_action);
                }
                if ($userExpense) {


                    $userExpense->payThruReference = $data->transactionDetails->payThruReference;
                    $userExpense->fiName = $data->transactionDetails->fiName;
                    $userExpense->status = $data->transactionDetails->status;
                    $userExpense->amount = $data->transactionDetails->amount;
                    $userExpense->responseCode = $data->transactionDetails->responseCode;
                    $userExpense->paymentMethod = $data->transactionDetails->paymentMethod;
                    $userExpense->commission = $data->transactionDetails->commission;
                    // Check if residualAmount is negative
                    if ($data->transactionDetails->residualAmount < 0) {
                        $userExpense->negative_amount = $data->transactionDetails->residualAmount;
                    } else {
                        $userExpense->negative_amount = 0;
                    }
                    $userExpense->residualAmount = $data->transactionDetails->residualAmount ?? 0;
                    // $userExpense->residualAmount = $data->transactionDetails->residualAmount;
                    $userExpense->resultCode = $data->transactionDetails->resultCode;
                    $userExpense->responseDescription = $data->transactionDetails->responseDescription;
                    $userExpense->providedEmail = $data->transactionDetails->customerInfo->providedEmail;
                    $userExpense->providedName = $data->transactionDetails->customerInfo->providedName;
                    $userExpense->remarks = $data->transactionDetails->customerInfo->remarks;
//		$userExpense->minus_residual = $new_minus_residual;
                    $userExpense->save();
                    $activePayment = new Active([
                        'paymentReference' => $data->transactionDetails->paymentReference,
                        'product_id' => $productId,
                        'product_type' => $modelType
                    ]);
                    $activePayment->save();
                    Log::info("Payment reference saved in ActivePayment table");
                    //	Log::info("minus_residual updated" . $userExpense->minus_residual);
                    Log::info("User expense updated");
                } else {
                    Log::info("User expense not found for payment reference: " . $data->transactionDetails->paymentReference);
                }

                http_response_code(200);
            } elseif ($data->notificationType == 2) {
                if (isset($data->transactionDetails->transactionReferences[0])) {
                    $transactionReferences = $data->transactionDetails->transactionReferences[0];
                    Log::info("Received withdrawal notification for transaction references: " . $transactionReferences);

                    // Update withdrawal
                    $withdrawal = Withrawal::where('transactionReferences', $transactionReferences)->first();
                    $product_action = "withdrawal";
                    $referral = ReferralSetting::where('status', 'active')
                        ->latest('updated_at')
                        ->first();
                    if ($referral) {
                        $this->referral->checkSettingEnquiry($modelType, $product_action);
                    }
                    if ($withdrawal) {
                        $uniqueId = $withdrawal->uniqueId;

                        $updatePaybackWithdrawal = Withrawal::where([
                            'transactionReferences' => $transactionReferences,
                            'uniqueId' => $uniqueId
                        ])->first();

                        if ($updatePaybackWithdrawal) {
                            $updatePaybackWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
                            $updatePaybackWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
                            // Set the status to "success"
                            $updatePaybackWithdrawal->status = 'success';

                            $updatePaybackWithdrawal->save();

                            Log::info("Payback withdrawal updated");
                        } else {
                            Log::info("Payback withdrawal not found for transaction references: " . $transactionReferences);
                        }
                    } else {
                        Log::info("Withdrawal not found for transaction references: " . $transactionReferences);
                    }
                } else {
                    Log::info("Transaction references not found in the webhook data");
                }
            }

            http_response_code(200);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }


    public function Collection(Request $request)
    {
        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);
        $secret = env('PayThru_App_Secret');
        $productId = env('PayThru_expense_productid');
        $hash = hash('sha512', $timestamp . $secret);
        $AppId = env('PayThru_ApplicationId');
        $prodUrl = env('PayThru_Base_Live_Url');

        $charges = env('PayThru_Withdrawal_Charges');

        $requestAmount = $request->amount;

      //  $latestCharge = Charge::orderBy('updated_at', 'desc')->first();

     //   $applyCharges = false; // Default value until logic determines whether charges should be applied

//        if ($latestCharge) {
//            $applyCharges = $this->chargeService->applyCharges($latestCharge);
//        }

        $latestWithdrawal = Payment::where('user_id', auth()->user()->id)
            ->where('stat', 1)
            ->latest()
            ->pluck('minus_residual')
            ->first();

        if ($requestAmount < 100) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

        if ($latestWithdrawal !== null) {
            if ($requestAmount > $latestWithdrawal) {
                return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
            }
            $minusResidual = $latestWithdrawal - $requestAmount;
        }

        $refundmeAmountWithdrawn = $requestAmount - $charges;

        $acct = $request->account_number;

        $bank = Bank::where('user_id', auth()->user()->id)
            ->where('account_number', $acct)
            ->first();

        if (!$bank) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        $beneficiaryReferenceId = $bank->referenceId;

        $token = $this->paythruService->handle();

        if (!$token) {
            return "Token retrieval failed";
        } elseif (is_string($token) && strpos($token, '403') !== false) {
            return response()->json([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ], 403);
        }

        $data = [
            'productId' => $productId,
            'amount' => $refundmeAmountWithdrawn,
            'beneficiary' => [
                'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];

        $url = $prodUrl . '/transaction/settlement';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Settlement request failed'], 500);
        }

        Payment::where('user_id', auth()->user()->id)->where('stat', 1)
            ->latest()->update(['minus_residual' => $minusResidual]);

        $withdrawal = new Withrawal([
            'account_number' => $request->account_number,
            'description' => $request->description,
            'beneficiary_id' => auth()->user()->id,
            'amount' => $refundmeAmountWithdrawn,
            'bank' => $request->bank,
            'charges' => 0,
            'uniqueId' => Str::random(10),
        ]);

        $withdrawal->save();

        $collection = $response->json();

        Log::info('API response: ' . json_encode($collection));
        $saveTransactionReference = Withrawal::where('beneficiary_id', Auth::user()->id)
            ->where('uniqueId', $withdrawal->uniqueId)
            ->update([
                'transactionReferences' => $collection['transactionReference'],
                'status' => $collection['message'],
            ]);

        return response()->json($saveTransactionReference, 200);
    }





    public function accountVerification(Request $request)
    {

        $user = Auth::user()->id;

        $prodUrl = env('PayThru_Base_Live_Url');
        $account = $request->account_number;
        $bankCode = $request->bankCode;

        $getLastName = User::where('id', $user)->first();
        $last = $getLastName->last_name;
        $first = $getLastName->first_name;
        $middle_name = $getLastName->middle_name;
        $fullName = $last.' '.$first.' '.$middle_name;
        $fullNames = $first.' '.$middle_name.' '.$last;

        $token = $this->paythruService->handle();
        if (!$token) {
            return "Token retrieval failed";
        } elseif (is_string($token) && strpos($token, '403') !== false) {
            return response()->json([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ], 403);
        }
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->get("https://services.paythru.ng/cardfree/bankinfo/nameInfo/$account/$bankCode");

        if ($response->successful()) {
            $details = $response->object();
            $getData = $details->data;
            return response()->json($details);
        }

        return response()->json(['error' => 'Account verification failed'], 400);


    }



    public function getPaymentWithdrawalTransaction()
    {
        $getWithdrawalTransaction = Withrawal::where('user_id', Auth::user()->id)->get();
        if($getWithdrawalTransaction->count() > 0)
        {
            return response()->json($getWithdrawalTransaction);
        }else{
            return response([
                'message' => 'transaction not found for this user'
            ], 404);
        }
    }

}
