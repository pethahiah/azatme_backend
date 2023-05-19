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


class WalletController extends Controller
{
    //

    public function createWallet(Request $request)
    {


        //Fetch User
        $getUser = Auth::user()->id;
        // To get the total amount own on paythru system
        $getUserExpenseTransactions = userExpense::where('principal_id', $getUser)->sum('residualAmount');
        $getUserKontributeTransactions = userGroup::where('reference_id', $getUser)->sum('residualAmount');
        $getUserBusinessTransactions = BusinessTransaction::where('owner_id', $getUser)->sum('residualAmount');
        $UserRecievedAmount = $getUserExpenseTransactions + $getUserKontributeTransactions + $getUserBusinessTransactions;
        //return $UserRecievedAmount;

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
        ]);
       
    }
    
}
