<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Expense;
use App\User;
use App\userExpense;
use App\Withdrawal;
use App\UserGroup;
use Carbon\Carbon;
use App\GroupWithdrawal;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\BusinessTransaction;
use App\BusinessWithdrawal;
use App\Wallet;
use App\Product;
use DB;
use Illuminate\Support\Facades\Log;
use App\Active;
use Illuminate\Database\QueryException;


class WalletController extends Controller
{
    //


  public function updateBalanceResidual()
{
    try {
        // Point 1: Fetch latest paymentReference from ActivePayment
        $latestPayment = Active::orderBy('updated_at', 'desc')->first();
         if ($latestPayment) {
        $latestPaymentReference = $latestPayment->paymentReference;

        // Check if paymentReference exists in userExpense table
        $userExpense = UserExpense::where('paymentReference', $latestPaymentReference)->first();
        if ($userExpense) {
            $residualAmount = $userExpense->residualAmount;
            Log::info("Minus residual updated successfully in userGroup table.". $residualAmount);
            $createdAt = $userExpense->updated_at;
            $updateTable = 'userExpense'; // To track which table to update later
        } else {
            // Check if paymentReference exists in userGroup table
            $userGroup = UserGroup::where('paymentReference', $latestPaymentReference)->first();
            if ($userGroup) {
                $residualAmount = $userGroup->residualAmount;
                $createdAt = $userGroup->updated_at;
                $updateTable = 'userGroup'; // To track which table to update later
            } else {
                // Check if paymentReference exists in BusinessTransaction table
                $businessTransaction = BusinessTransaction::where('paymentReference', $latestPaymentReference)->first();
                if ($businessTransaction) {
                    $residualAmount = $businessTransaction->residualAmount;
                    $createdAt = $businessTransaction->updated_at;
                    $updateTable = 'businessTransaction'; // To track which table to update later
                } else {
                    Log::error("Payment reference not found in userExpense, userGroup, or BusinessTransaction table.");

                }
            }
        }
 		// Point 3: Check if created_at matches the payment's created_at
        if ($createdAt->equalTo($latestPayment->updated_at)) {
            // Point 4: Update the minus_residual on appropriate table
            $authId = Auth::id();
            if ($updateTable === 'userExpense') {
                $userExpenseToUpdate = UserExpense::where('principal_id', $authId)->first();
                if ($userExpenseToUpdate && ($userExpenseToUpdate->stat === 0 || $userExpenseToUpdate->stat === null)) {
                    $updatedMinusResidual = $userExpenseToUpdate->minus_residual + $residualAmount;
                    Log::info("Minus residual updated successfully in userGroup table.". $updatedMinusResidual);
                    $userExpenseToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                        'stat' => 1, // Set the flag to indicate update
                    ]);
                    Log::info("Minus residual updated successfully in userGroup table.". $userExpenseToUpdate);
                    Log::info("Minus residual updated successfully in userExpense table.");
                } else {
                    if ($userExpenseToUpdate && $userExpenseToUpdate->stat == 1) {
                        Log::error("Minus residual already updated for user expense record.");
                    } else {
                        Log::error("No user expense record found.");
                    }
                }
            } elseif ($updateTable === 'userGroup') {
                $userGroupToUpdate = UserGroup::where('reference_id', $authId)->first();
               if ($userGroupToUpdate && ($userGroupToUpdate->stat === 0 || $userGroupToUpdate->stat === null)) {
                    $updatedMinusResidual = $userGroupToUpdate->minus_residual + $residualAmount;
                    $userGroupToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                        'stat' => 1, // Set the flag to indicate update
                    ]);

                    Log::info("Minus residual updated successfully in userGroup table.");
                } else {
                    if ($userGroupToUpdate && $userGroupToUpdate->stat == 1) {
                        Log::error("Minus residual already updated for user group record.");
                    } else {
                        Log::error("No user expense record found.");
                    }
                }
            } elseif ($updateTable === 'businessTransaction') {
                // Perform the update in BusinessTransaction table
                $businessTransactionToUpdate = BusinessTransaction::where('owner_id', $authId)->first();
                if ($businessTransactionToUpdate && ($businessTransactionToUpdate->stat === 0 || $businessTransactionToUpdate->stat === null)) {
                    $updatedMinusResidual = $businessTransactionToUpdate->minus_residual + $residualAmount;
                    $businessTransactionToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                        'stat' => 1, // Set the flag to indicate update
                    ]);

                    Log::info("Minus residual updated successfully in BusinessTransaction table.");
                } else {
                    if ($businessTransactionToUpdate && $businessTransactionToUpdate->stat == 1) {
                        Log::error("Minus residual already updated for user business record.");
                    } else {
                        Log::error("No user business record found.");
                    }
                }
        } else {
            Log::error("Created_at values do not match.");
        }
                } else {
                    return response()->json(['error' => 'No ActivePayment record found.'], 404);
                }
        
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
	

        // To get the total amount own on paythru system
        $getUserExpenseTransactions = userExpense::where('principal_id', $getUser)->sum('residualAmount');
        $getUserKontributeTransactions = userGroup::where('reference_id', $getUser)->sum('residualAmount');
        $getUserBusinessTransactions = BusinessTransaction::where('owner_id', $getUser)->sum('residualAmount');
        $UserRecievedAmount = $getUserExpenseTransactions + $getUserKontributeTransactions + $getUserBusinessTransactions;
        //return $UserRecievedAmount;

	$kontributeTransactions = UserGroup::where('reference_id', $getUser)->latest()->pluck('minus_residual')->first();
//	$kontributeTransactions = UserGroup::where('reference_id', $getUser)
  // 	 ->selectRaw('GREATEST(created_at, updated_at) as combined_timestamp, minus_residual')
   //	 ->orderBy('combined_timestamp', 'desc')
   //	 ->pluck('minus_residual')
   //	 ->first();
	
//	$RefundmeTransactions = userExpense::where('principal_id', $getUser)
  // 	 ->selectRaw('GREATEST(created_at, updated_at) as combined_timestamp, minus_residual')
   //	 ->orderBy('combined_timestamp', 'desc')
   //	 ->pluck('minus_residual')
   //	 ->first();


//	Log::info("Minus residual updated successfully in userGroup table.". $RefundmeTransactions);

//	$BusinessTransactions = BusinessTransaction::where('owner_id', $getUser)
  //       ->selectRaw('GREATEST(created_at, updated_at) as combined_timestamp, minus_residual')
    //     ->orderBy('combined_timestamp', 'desc')
      //   ->pluck('minus_residual')
        // ->first();
	$RefundmeTransactions = userExpense::where('principal_id', $getUser)->latest()->pluck('minus_residual')->first();
	$BusinessTransactions = BusinessTransaction::where('owner_id', $getUser)->latest()->pluck('minus_residual')->first();

         // To get the total amount collected from paythru system
        $getUserExpenseTransactionsWithdrawn = Withdrawal::where('beneficiary_id', $getUser)->sum('paymentAmount');
        $getUserKontributeTransactionsWithdrawn = GroupWithdrawal::where('beneficiary_id', $getUser)->sum('paymentAmount');
        $getUserBusinessTransactionsWithdrawn = BusinessWithdrawal::where('beneficiary_id', $getUser)->sum('paymentAmount');
        $WithdrawnAmount = $getUserExpenseTransactionsWithdrawn + $getUserKontributeTransactionsWithdrawn + $getUserBusinessTransactionsWithdrawn;
        //return $WithdrawnAmount;
        
        // Amount Expecting on each prodcts
        $anountExpectingOnRefundMe = Expense::where('user_id', $getUser)->whereNotNull('subcategory_id')->sum('amount');
        $anountExpectingOnKontribute = Expense::where('user_id', $getUser)->whereNull('subcategory_id')->sum('amount');
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
            'TotalSumReceivedFromPaythruOnBusiness' => $getUserBusinessTransactions,
            'TotalSumReceivedFromPaythruOnExpense' => $getUserExpenseTransactions,
            'TotalSumReceivedFromPaythruOnKontribute' => $getUserKontributeTransactions,
            'UserExpenseTransactionsWithdrawn' => $getUserExpenseTransactionsWithdrawn,
            'UserKontributeTransactionsWithdrawn' => $getUserKontributeTransactionsWithdrawn,
            'UserBusinessTransactionsWithdrawn' => $getUserBusinessTransactionsWithdrawn,
	    'AmountLeftForWithdrawRefundme' => $RefundmeTransactions,
	    'AmountLeftForWithdrawGroup' => $kontributeTransactions,
	    'AmountLeftForWithdrawBusiness' => $BusinessTransactions,
        ]);
       
 }

}
