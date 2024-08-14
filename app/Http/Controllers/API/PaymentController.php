<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ReferralSetting;
use App\Models\Trip;
use App\Services\MposService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;


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
                    $withdrawal = Withdrawal::where('transactionReferences', $transactionReferences)->first();
                    $product_action = "withdrawal";
                    $referral = ReferralSetting::where('status', 'active')
                        ->latest('updated_at')
                        ->first();
                    if ($referral) {
                        $this->referral->checkSettingEnquiry($modelType, $product_action);
                    }
                    if ($withdrawal) {
                        $uniqueId = $withdrawal->uniqueId;

                        $updatePaybackWithdrawal = Withdrawal::where([
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


}
