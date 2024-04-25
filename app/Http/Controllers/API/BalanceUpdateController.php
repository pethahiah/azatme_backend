<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UserExpense;
use Auth;
use App\UserGroup;
use App\BusinessTransaction;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ActivePayment;
use Illuminate\Database\QueryException;



class BalanceUpdateController extends Controller
{
    //

  public function updateBalanceResidual(Request  $request)
{
    try {
        // Point 1: Fetch latest paymentReference from ActivePayment
        $latestPayment = ActivePayment::orderBy('created_at', 'desc')->first();
        $latestPaymentReference = $latestPayment->paymentReference;

        // Check if paymentReference exists in userExpense table
        $userExpense = UserExpense::where('paymentReference', $latestPaymentReference)->first();
        if ($userExpense) {
            $residualAmount = $userExpense->residualAmount;
            $createdAt = $userExpense->created_at;
            $updateTable = 'userExpense'; // To track which table to update later
        } else {
            // Check if paymentReference exists in userGroup table
            $userGroup = UserGroup::where('paymentReference', $latestPaymentReference)->first();
            if ($userGroup) {
                $residualAmount = $userGroup->residualAmount;
                $createdAt = $userGroup->created_at;
                $updateTable = 'userGroup'; // To track which table to update later
            } else {
                // Check if paymentReference exists in BusinessTransaction table
                $businessTransaction = BusinessTransaction::where('paymentReference', $latestPaymentReference)->first();
                if ($businessTransaction) {
                    $residualAmount = $businessTransaction->residualAmount;
                    $createdAt = $businessTransaction->created_at;
                    $updateTable = 'businessTransaction'; // To track which table to update later
                } else {
                    Log::error("Payment reference not found in userExpense, userGroup, or BusinessTransaction table.");

                }
            }
        }

        // Point 3: Check if created_at matches the payment's created_at
        if ($createdAt->equalTo($latestPayment->created_at)) {
            // Point 4: Update the minus_residual on appropriate table
            $authId = Auth::id();
            if ($updateTable === 'userExpense') {
                $userExpenseToUpdate = UserExpense::where('principal_id', $authId)->first();
                if ($userExpenseToUpdate) {
                    $updatedMinusResidual = $userExpenseToUpdate->minus_residual + $residualAmount;
                    $userExpenseToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                    ]);

                    Log::info("Minus residual updated successfully in userExpense table.");
                } else {
                    Log::error("No user expense record found.");
                }
            } elseif ($updateTable === 'userGroup') {
                $userGroupToUpdate = UserGroup::where('reference_id', $authId)->first();
                if ($userGroupToUpdate) {
                    $updatedMinusResidual = $userGroupToUpdate->minus_residual + $residualAmount;
                    $userGroupToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                    ]);

                    Log::info("Minus residual updated successfully in userGroup table.");
                } else {
                    Log::error("No user group record found.");
                }
            } elseif ($updateTable === 'businessTransaction') {
                // Perform the update in BusinessTransaction table
                $businessTransactionToUpdate = BusinessTransaction::where('owner_id', $authId)->first();
                if ($businessTransactionToUpdate) {
                    $updatedMinusResidual = $businessTransactionToUpdate->minus_residual + $residualAmount;
                    $businessTransactionToUpdate->update([
                        'minus_residual' => $updatedMinusResidual,
                    ]);

                    Log::info("Minus residual updated successfully in BusinessTransaction table.");
                } else {
                    Log::error("No business transaction record found.");
                }
            }
        } else {
            Log::error("Created_at values do not match.");
        }
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error($e->getMessage());
        return response()->json(['error' => 'An error occurred'], 500);
    }
}



}
