<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Expense;
use App\User;
use App\KontributeBalance;
use App\OpenActive;
use App\userExpense;
use App\Withdrawal;
use App\UserGroup;
use App\Donor;
use Carbon\Carbon;
use App\GroupWithdrawal;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\BusinessTransaction;
use App\BusinessWithdrawal;
use App\Wallet;
use App\Product;
use DB;
use App\AjoWithdrawal;
use App\Invitation;
use Illuminate\Support\Facades\Log;
use App\Active;
use App\AjoContributor;
use App\AjoBalanace;
use Illuminate\Database\QueryException;


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
            $userExpense = userExpense::where('paymentReference', $latestPaymentReference)->first();
            $userGroup = UserGroup::where('paymentReference', $latestPaymentReference)->first();
            $businessTransaction = BusinessTransaction::where('paymentReference', $latestPaymentReference)->first();

            // Initialize variables for later use
            $residualAmount = 0;
            $updateTable = '';

            if ($userExpense) {
                $residualAmount = $userExpense->residualAmount;
                $updatedAt = $userExpense->updated_at;
                $updateTable = 'userExpense';
            } elseif ($userGroup) {
                $residualAmount = $userGroup->residualAmount;
                $updatedAt = $userGroup->updated_at;
                $updateTable = 'userGroup';
            } elseif ($businessTransaction) {
                $residualAmount = $businessTransaction->residualAmount;
                $updatedAt = $businessTransaction->updated_at;
                $updateTable = 'businessTransaction';
            }
                else {
                Log::error("Payment reference not found in userExpense, userGroup, or BusinessTransaction table.");
            }

            // Point 3: Check if created_at matches the payment's created_at
            if ($updatedAt->equalTo($latestPayment->updated_at)) {
                // Point 4: Update the minus_residual on the appropriate table
                $authId = Auth::id();

                if ($updateTable === 'userExpense') {
                    $userExpenseToUpdate = userExpense::where('principal_id', $authId)->where('paymentReference', $latestPaymentReference)->first();

                    if ($userExpenseToUpdate && ($userExpenseToUpdate->stat === 0 || $userExpenseToUpdate->stat === null)) {
                        $userBalance = userExpense::where('principal_id', $authId)
                        ->where('stat', 1)
                        ->latest()
                        ->pluck('minus_residual')
                        ->first();

                        if ($userBalance) {
                            $residualAmount += $userBalance;
                        }

                        $userExpenseToUpdate->update([
                            'minus_residual' => $residualAmount,
                            'stat' => 1, // Set the flag to indicate update
                        ]);

                        Log::info("Minus residual updated successfully in userExpense table.");
                    } else {
                        if ($userExpenseToUpdate && $userExpenseToUpdate->stat === 1) {
                            Log::info("Minus residual already updated for user expense record.");
                        } else {
                            Log::info("good");
                        }
                    }
                } elseif ($updateTable === 'userGroup') {
                    $userGroupToUpdate = UserGroup::where('reference_id', $authId)->where('paymentReference', $latestPaymentReference)->first();

                    if ($userGroupToUpdate && ($userGroupToUpdate->stat === 0 || $userGroupToUpdate->stat === null)) {
                        $userGroupBalance = UserGroup::where('reference_id',  $authId)
                        ->where('stat', 1)
                        ->latest()
                        ->pluck('minus_residual')
                        ->first();


                        if ($userGroupBalance) {
                            $residualAmount += $userGroupBalance;
                        }

                        $userGroupToUpdate->update([
                            'minus_residual' => $residualAmount,
                            'stat' => 1, // Set the flag to indicate update
                        ]);

                        Log::info("Minus residual updated successfully in userGroup table.");
                    } else {
                        if ($userGroupToUpdate && $userGroupToUpdate->stat === 1) {
                            Log::info("Minus residual already updated for user group record for group.");
                        } else {
                            Log::info("good for group");
                        }
                    }
                } elseif ($updateTable === 'businessTransaction') {
                    $businessTransactionToUpdate = BusinessTransaction::where('owner_id', $authId)->where('paymentReference', $latestPaymentReference)->first();

                    if ($businessTransactionToUpdate && ($businessTransactionToUpdate->stat === 0 || $businessTransactionToUpdate->stat === null)) {
                        $userBusinessBalance = BusinessTransaction::where('owner_id', $authId)
                         ->where('stat', 1)
                        ->latest()
                        ->pluck('minus_residual')
                        ->first();

                        if ($userBusinessBalance) {
                            $residualAmount += $userBusinessBalance;
                        }

                        $businessTransactionToUpdate->update([
                            'minus_residual' => $residualAmount,
                            'stat' => 1, // Set the flag to indicate update
                        ]);

                        Log::info("Minus residual updated successfully in BusinessTransaction table.");
                    } else {
                        if ($businessTransactionToUpdate && $businessTransactionToUpdate->stat === 1) {
                            Log::info("Minus residual already updated for user business record.");
                        } else {
                            Log::info("good for business");
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
        $getUserExpenseTransactions = userExpense::where('principal_id', $getUser)->sum('residualAmount');

	//Filter Close/Open Kontribute from ResidualAmount
        $getUserOpenKontribute = userGroup::where('reference_id', Auth::user()->id)
          ->whereNull('paymentReference')
          ->whereNotNull('merchantReference')
          ->sum('residualAmount');

        $getUserCloseKontribute = userGroup::where('reference_id', Auth::user()->id)
          ->whereNull('merchantReference')
          ->whereNotNull('paymentReference')
          ->sum('residualAmount');

        $getUserKontributeTransactions = $getUserOpenKontribute +  $getUserCloseKontribute;
	$kontributeBalance = GroupWithdrawal::where('beneficiary_id', Auth::user()->id)->whereNotNull('status')->sum('amount');
	$kontributeCharges = GroupWithdrawal::where('beneficiary_id', Auth::user()->id)->whereNotNull('status')->sum('charges');
	$kontributeTransactions = $getUserKontributeTransactions - ($kontributeBalance + $kontributeCharges);

	$latestKontributeBalance = KontributeBalance::where('user_id', $getUser)->latest()->first();

	if ($latestKontributeBalance && $latestKontributeBalance->balance != $kontributeTransactions) {
    	// Create a new entry in KontributeBalance table
    	$kontributeBalanceEntry = KontributeBalance::create([
        'user_id' => $getUser,
        'balance' => $kontributeTransactions,
	'action' => 'credit',
   	 ]);
	} else {
    	$kontributeBalanceEntry = $latestKontributeBalance;
	}

        $getUserBusinessTransactions = BusinessTransaction::where('owner_id', $getUser)->sum('residualAmount');
	$getAjoTransactions = Invitation::where('email', Auth::user()->email)->sum('residualAmount');
        $UserRecievedAmount = $getUserExpenseTransactions + $getUserKontributeTransactions + $getUserBusinessTransactions + $getAjoTransactions;


	$RefundmeTransactions = userExpense::where('principal_id', $getUser)->where('stat', 1)->latest()->pluck('minus_residual')->first();

	$BusinessTransactions = BusinessTransaction::where('owner_id', $getUser)->where('stat', 1)->latest()->pluck('minus_residual')->first();

	$AjoBalance = AjoWithdrawal::where('beneficiary_id', $getUser)->whereNotNull('status')->sum('amount');
	$AjoCharges = AjoWithdrawal::where('beneficiary_id', $getUser)->whereNotNull('status')->sum('charges');
	$AjoTransactions =  $getAjoTransactions - ($AjoBalance + $AjoCharges);

	$latestAjoBalance =AjoBalanace::where('user_id', $getUser)
   	 ->latest()
   	 ->first();
	if ($latestAjoBalance && $latestAjoBalance->balance != $AjoTransactions) {
    	// Create a new entry in AjoBalance table
    	$ajoBalanceEntry =AjoBalanace::create([
        'user_id' => $getUser,
        'balance' => $AjoTransactions,
	'action' => 'credit',
   	 ]);
	} else {
    	$ajoBalanceEntry = $latestAjoBalance;
	}


         // To get the total amount collected from paythru system
        $getUserExpenseTransactionsWithdrawn = Withdrawal::where('beneficiary_id', $getUser)->sum('amount');
        $getUserBusinessTransactionsWithdrawn = BusinessWithdrawal::where('beneficiary_id', $getUser)->sum('amount');
	$getUserAjoWithdrawn = $AjoBalance + $AjoCharges;
	$getUserKontributeTransactionsWithdrawn = $kontributeBalance + $kontributeCharges;
        $WithdrawnAmount = $getUserExpenseTransactionsWithdrawn + $getUserKontributeTransactionsWithdrawn + $getUserBusinessTransactionsWithdrawn + $getUserAjoWithdrawn;

        // Amount Expecting on each prodcts
        $anountExpectingOnRefundMe = Expense::where('user_id', $getUser)->whereNotNull('subcategory_id')->where('confirm', 1)->sum('amount');
        $anountExpectingOnKontribute = Expense::where('user_id', $getUser)->whereNull('subcategory_id')->where('confirm', 1)->sum('amount');
        $anountExpectingOnBusiness = Product::where('user_id', $getUser)->sum('amount');

        // Wallet Balance
        $walletBalance = $UserRecievedAmount - $WithdrawnAmount;

        // Create Wallet
        $wallet = Wallet::create([
        'user_id' => $getUser,
        'amountExpectedRefundMe' => $anountExpectingOnRefundMe,
        'amountExpectedKontribute' => $anountExpectingOnKontribute,
        'amountExpectedBusiness' => $anountExpectingOnBusiness,
        'residual_amount' => $UserRecievedAmount,
        'amount_paid_by_paythru' => $WithdrawnAmount,
        'balance' => $walletBalance,
        ]);

        // Output all data

        return response()->json([
            'Wallet' => $wallet,
	    'TotalSumReceivedFromPaythruOnAjo' => $getAjoTransactions,
            'TotalSumReceivedFromPaythruOnBusiness' => $getUserBusinessTransactions,
            'TotalSumReceivedFromPaythruOnExpense' => $getUserExpenseTransactions,
            'TotalSumReceivedFromPaythruOnKontribute' => $getUserKontributeTransactions,
	    'UserAjoTransactionWithdrawn' => $getUserAjoWithdrawn,
            'UserExpenseTransactionsWithdrawn' => $getUserExpenseTransactionsWithdrawn,
            'UserKontributeTransactionsWithdrawn' => $getUserKontributeTransactionsWithdrawn,
            'UserBusinessTransactionsWithdrawn' => $getUserBusinessTransactionsWithdrawn,
	    'AmountLeftForWithdrawRefundme' => $RefundmeTransactions,
	    'AmountLeftForWithdrawGroup' => $kontributeTransactions,
	    'AmountLeftForWithdrawBusiness' => $BusinessTransactions,
	   'AmountLeftForWithdrawalAjo' => $AjoTransactions,
        ]);
 }

}
