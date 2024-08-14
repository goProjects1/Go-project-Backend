<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Active;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    //
    public function updateBalanceResidual()
    {
        try {
            // Point 1: Fetch the latest paymentReference from ActivePayment
            $latestPayment = Active::orderBy('updated_at', 'desc')->first();
            if ($latestPayment) {
                $latestPaymentReference = $latestPayment->paymentReference;

                // Check if paymentReference exists in any of the three tables
                $userExpense = Payment::where('paymentReference', $latestPaymentReference)->first();


                // Initialize variables for later use
                $residualAmount = 0;
                $updateTable = '';

                if ($userExpense) {
                    $residualAmount = $userExpense->residualAmount;
                    $updatedAt = $userExpense->updated_at;
                    $updateTable = 'Payment';
                }
                else {
                    Log::error("Payment reference not found in payment.");
                }

                // Point 3: Check if created_at matches the payment's created_at
                if ($updatedAt->equalTo($latestPayment->updated_at)) {
                    // Point 4: Update the minus_residual on the appropriate table
                    $authId = Auth::id();

                    if ($updateTable === 'Payment') {
                        $userExpenseToUpdate = Payment::where('principal_id', $authId)->where('paymentReference', $latestPaymentReference)->first();

                        if ($userExpenseToUpdate && ($userExpenseToUpdate->stat === 0 || $userExpenseToUpdate->stat === null)) {
                            $userBalance = Payment::where('principal_id', $authId)
                                ->where('stat', 1)
                                ->latest()
                                ->pluck('minus_residual')
                                ->first();

                            if ($userBalance) {
                                $residualAmount += $userBalance;
                            }

                            $userExpenseToUpdate->update([
                                'minus_residual' => $residualAmount,
                                'stat' => 1,
                            ]);

                            Log::info("Minus residual updated successfully in userExpense table.");
                        } else {
                            if ($userExpenseToUpdate && $userExpenseToUpdate->stat === 1) {
                                Log::info("Minus residual already updated for user expense record.");
                            } else {
                                Log::info("good");
                            }
                        }
                    }
                } else {
                    Log::error("Created_at values do not match.");
                }
            } else {
                return response()->json(['error' => 'No ActivePayment record found.'], 404);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }


    public function createWallet(Request $request)
    {
        //Fetch User
        $getUser = Auth::user()->id;
        $this->updateBalanceResidual();
        $charges = env('PayThru_Withdrawal_Charges');

        // To get the total amount own on paythru system
        $getUserExpenseTransactions = Payment::where('user_id', $getUser)->sum('residualAmount');

        $RefundmeTransactions = Payment::where('user_id', $getUser)->where('stat', 1)->latest()->pluck('minus_residual')->first();

        // To get the total amount collected from paythru system
        $getUserExpenseTransactionsWithdrawn = Withdrawal::where('user_id', $getUser)->sum('amount');
        $WithdrawnAmount = $getUserExpenseTransactionsWithdrawn;


        // Wallet Balance
        $walletBalance = $getUserExpenseTransactions - $WithdrawnAmount;

        // Create Wallet
        $wallet = Wallet::create([
            'user_id' => $getUser,
            'amountExpectedRefundMe' => $getUserExpenseTransactions,
            'residual_amount' => $getUserExpenseTransactions,
            'amount_paid_by_paythru' => $WithdrawnAmount,
            'balance' => $walletBalance,
        ]);

        // Output all data

        return response()->json([
            'Wallet' => $wallet,
        ]);
    }
}
