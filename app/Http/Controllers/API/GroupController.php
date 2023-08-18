<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\GroupRequest;
use App\Http\Requests\userGroupRequest;
use App\Expense;
use Illuminate\Support\Str;
use Auth;
use App\Bank;
use App\User;
use Mail;
use Carbon\Carbon;
use App\Mail\KontributMail;
use App\UserGroup;
use App\GroupWithdrawal;
use App\Jobs\ProcessBulkExcel;
use Illuminate\Http\Response;
use App\Helper\Reply;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;
use Datetime;
use App\Exports\ExpenseExport;
use Excel;
use Illuminate\Support\Facades\Log;
use App\Mail\SendUserInviteMail;
use App\Setting;
use DB;
use App\Invited;
use App\Active;
use App\Services\PaythruService;
use Illuminate\Database\QueryException;



class GroupController extends Controller
{
    //


public $paythruService;

public function __construct(PaythruService $paythruService)
  {
      $this->paythruService = $paythruService;
  }

public function createGroup(Request $request)
    {

    $expense = Expense::create([
        'name'=> $request->name,
        'description' => $request->description,
        'uique_code'=> Str::random(10),
        'amount' => $request->amount,
        'user_id' => Auth::user()->id,
        ]);
        
        return response()->json($expense);
        
        }
        
        public function updateGroup(Request $request, $id)
    {
    $user = Auth::user()->id;
    $get = Expense::where('id', $id)->first();
    $now = $get->user_id;
   // return $user;
    if($now != $user)
    {
         return response()->json(['You dont have edit right over this Kontribute'], 422);
    
        
    }else{
        $update = Expense::find($id);
        $update->update($request->all());
        return response()->json($update);   
}
}



// public function inviteUsersToGroup(Request $request, $groupId)
// {
//        // $group = expense::where('id', $groupId)->whereNull('category_id')->get();
//         $group = expense::findOrFail($groupId);
// //return $group;
//         $input['email'] = $request->input('email');
//        $ProductId = env('PayThru_kontribute_productid');
//        $current_timestamp= now();
//        $timestamp = strtotime($current_timestamp);
//        $secret = env('PayThru_App_Secret');
//        $hash = hash('sha512', $timestamp . $secret);
//        $amt = $group->amount;
//        $hashSign = hash('sha512', $amt . $secret);
//        $PayThru_AppId = env('PayThru_ApplicationId');
//        $prodUrl = env('PayThru_Base_Live_Url');
// //return $amt;
//       $emails = $request->email;
//       if($group)
//         {
//       if($emails)
//       {
//       $emailArray = (explode(';', $emails));
//       $count = count($emailArray);
// //     return response()->json($emailArray);
      
//       $payers = [];
//       $totalpayable = 0;
      
//       foreach ($emailArray as $key => $em) {
//           //process each user here as each iteration gives you each email
//   //        $user = User::where('email', $em)->first();
// 	$user = Invited::where('auth_id', Auth::user()->id)->where('email', $em)->first();
//   //        return $em;
//         $payable = 0;
  
//         if($request['split_method_id'] == 1)
//         {
//             $payable = $group->amount;
//         } elseif($request['split_method_id'] == 3)
//         {
//           if(isset($request->percentage))
//           {
//             $payable = $group->amount*$request->percentage/100;
//           }elseif(isset($request->percentage_per_user))
//           {
//             $ppu = json_decode($request->percentage_per_user);
//             $payable = $ppu->$em*$group->amount/100;
//           }
//         }elseif($request['split_method_id'] == 2)
//         {
//          $payable = round(($group->amount / $count), 2);
//             if ($key == $count - 1) {
//         $payable = $group->amount - (round($payable, 2) * ($count - 1));
//         }
//         }
// //return $payable;
//         $paylink_expiration_time = Carbon::now()->addMinutes(15);


// $userGroup = userGroup::where('reference_id', Auth::user()->id)
//       ->where('group_id', $groupId)
//       ->selectRaw('SUM(amount_payable) AS checkAmountPayable, SUM(residualAmount) AS totalResidual')
//       ->first();

//    $checkAmountPayable = $userGroup->checkAmountPayable;
//    $totalResidual = $userGroup->totalResidual;

//  if ($group->amount == $checkAmountPayable) {
//         if ($totalResidual == $group->amount) {
//             // Payment is completely paid
//             return response([
//                 'message' => 'Your payment is already completed.'
//             ], 422);
//         } else {
//             // Refundme is completed
//             return response([
//                 'message' => 'You cannot request for an amount greater than your kontribute. Kontribute is completed.'
//             ], 422);
//         }
//     }

// //return $group->name;

//           $info = userGroup::create([
//             'reference_id' => Auth::user()->id,
//             'group_id' => $group->id,
//             'name' => $group->name,
// 	    'first_name' => $user->first_name,
//             'last_name' => $user->last_name,
//             'uique_code' => $group->uique_code,
//             'email' => $em,
//             'description' => $group['description'],
//             'split_method_id' => $request['split_method_id'],
//             'amount_payable' => $payable,
//             'actualAmount' => $group->amount,
//             'linkExpireDateTime'=> $paylink_expiration_time,
//             'bankName' => $request['bankName'],
//             'account_name' => $request['account_name'],
//             'bankCode' => $request['bankCode'],
//             'account_number' => $request['account_number'],
// //	    'uidd'=> Str::random(10),
//           ]);
      
//          $payers[] =  ["payerEmail" => $em, "paymentAmount" => $info->amount_payable];
//          $totalpayable = $totalpayable + $info->amount_payable;
//          $paylink_expiration_time = Carbon::now()->addMinutes(15);
//       }

//       $token = $this->paythruService->handle();

//       //return $token;

//       // Send payment request to paythru  
//       $data = [
//         'amount' => $group->amount,
//         'productId' => $ProductId,
//         'transactionReference' => time().$group->id,
//         'paymentDescription' => $group->description,
//         'paymentType' => 1,
//         'sign' => $hashSign,
//         'expireDateTime'=> $paylink_expiration_time,
//         'displaySummary' => false,
//         'splitPayInfo' => [
//             'inviteSome' => false,
//             'payers' => $payers
//           ]
//         ];
              
// //      return $data;
//     $url = $prodUrl;
//     $urls = $url.'/transaction/create';
//     //return $urls;
     
     
//     $response = Http::withHeaders([
//         'Content-Type' => 'application/json',
//         'Authorization' => $token,
//   ])->post($urls, $data);
//       if($response->failed())
//       {
//         return false;
//       }else{
//         $transaction = json_decode($response->body(), true);
// //       return $transaction;
//         $splitResult = $transaction['splitPayResult']['result'];
//         foreach($splitResult as $key => $slip)
//         {
// 	   	$authmail = Auth::user();
// 		$userss = Invited::where('auth_id', Auth::user()->id)->where('email', $slip['receipient'])->first();
//                 $uxer = $userss->first_name;
// 		 Mail::to($slip['receipient'], $authmail['name'], $uxer)->send(new KontributMail($slip, $authmail, $uxer));
//                // Mail::to($slip['receipient'])->send(new KontributMail($slip));
// //          Mail::to($slip['receipient'])->send(new KontributMail($slip));
//           $paylink = $slip['paylink'];
//        // return $paylink;
//             if($paylink)
//             {
//               $getLastString = (explode('/', $paylink));
//               $now = end($getLastString);
//               //return $now;
//         $userGroupReference = userGroup::where(['email' => $slip['receipient'], 'group_id' => $group->id, 'reference_id' => Auth::user()->id])->update([
//             'paymentReference' => $now,
//         ]);
//       }
//         }
//       }
//       return response()->json($transaction);
      
//     }
//          }else{
//               return response([
//                 'message' => "Id doesn't belong to this transaction category"
//             ], 401);
             
//          }
//         }  



public function inviteUsersToGroup(Request $request, $groupId)
        {
            $group = Expense::findOrFail($groupId);
        
            if (!$group) {
                return response(['message' => "Group not found"], 404);
            }
        
            $emails = $request->input('email');
            $paymentType = $request->input('paymentType', 1);
        
            if ($paymentType == 1) {
                return $this->processPaymentType1($group, $emails, $request);
            } elseif ($paymentType == 2) {
                return $this->processPaymentType2($group, $emails, $request);
            } else {
                return response(['message' => "Invalid payment type"], 400);
            }
        }
        
private function processPaymentType1($group, $emails, $request)
        {
            $productId = env('PayThru_kontribute_productid');
            $secret = env('PayThru_App_Secret');
            $prodUrl = env('PayThru_Base_Live_Url');
            $token = $this->paythruService->handle();
        
            [$payers, $totalPayable] = $this->calculatePayments($group, $emails, $request);
        
            $paymentData = $this->preparePaymentData($group, $productId, $secret, 1, $payers);
            $response = $this->sendPaymentRequest($token, $prodUrl, $paymentData);
        
            if ($response->failed()) {
                return response(['message' => "Payment request failed"], 500);
            }
        
            $transaction = $this->processPaymentResults($response, $payers, $group);
            return response()->json($transaction);
        }
        
private function processPaymentType2($group, $emails, $request)
        {
            $productId = env('PayThru_kontribute_productid');
            $secret = env('PayThru_App_Secret');
            $prodUrl = env('PayThru_Base_Live_Url');
            $token = $this->paythruService->handle();
        
            [$payers, $totalPayable] = $this->calculatePayments($group, $emails, $request);
        
            $paymentData = $this->preparePaymentDataForType2($group, $productId, $secret, 2, $payers);
            $response = $this->sendPaymentRequest($token, $prodUrl, $paymentData);
        
            if ($response->failed()) {
                return response(['message' => "Payment request failed"], 500);
            }
        
            $transaction = $this->processPaymentResults($response, $payers, $group);
            return response()->json($transaction);
        }

private function calculatePayments($group, $emails, $request)
{
    $paymentMethodId = $request['split_method_id'];
    $count = count($emails);

    $payers = [];
    $totalPayable = 0;

    foreach ($emails as $key => $email) {
        $user = User::where('email', $email)->first();
        $payable = 0;

        if ($paymentMethodId == 1) {
            $payable = $group->amount;
        } elseif ($paymentMethodId == 3) {
            if (isset($request->percentage)) {
                $payable = $group->amount * $request->percentage / 100;
            } elseif (isset($request->percentage_per_user)) {
                $ppu = json_decode($request->percentage_per_user);
                $payable = $ppu->$email * $group->amount / 100;
            }
        } elseif ($paymentMethodId == 2) {
            $payable = round(($group->amount / $count), 2);
            if ($key == $count - 1) {
                $payable = $group->amount - (round($payable, 2) * ($count - 1));
            }
        }

        $payers[] = ["payerEmail" => $email, "paymentAmount" => $payable];
        $totalPayable += $payable;
    }

    return [$payers, $totalPayable];
}

private function processPaymentResults($response, $payers, $group)
{
    $transactionData = json_decode($response->body(), true);
    // Iterate through the splitPayResult to handle each payer's data
    foreach ($transactionData['splitPayResult']['result'] as $payerData) {
        $payerEmail = $payerData['payerEmail'];
        $paymentReference = $payerData['paylink'];

        // Update the payment reference for the specific payer
        $this->updatePaymentReference($payerEmail, $group->id, $paymentReference);

        // Send email to the payer with relevant data
        Mail::to($payerEmail)->send(new KontributMail($payerData));
    }
    return $transactionData;
}

private function updatePaymentReference($payerEmail, $groupId, $paymentReference)
{
    // Update the payment reference for the specific payer and group
    userGroup::where([
        'email' => $payerEmail,
        'group_id' => $groupId,
        'reference_id' => Auth::user()->id
    ])->update([
        'paymentReference' => $paymentReference,
    ]);
}

private function sendPaymentRequest($token, $prodUrl, $paymentData)
{
    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post("$prodUrl/transaction/create", $paymentData);

        return $response;
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return response(['message' => "Payment request failed"], 500);
    }
}
















    public function UpdateTransactionGroupRequest(Request $request, $transactionId)
    {
      $transaction = userGroup::findOrFail($transactionId);
      //return $transaction;
      if($transaction->status == null)
      {
        $updateTransaction = $request->all();
        $update = userGroup::where('id', $transactionId)->update([
          'email' => $request->email,
      ]);
      return response([
                'message' => 'successful'
            ], 200);
      }else{
          return response([
                'message' => 'You cannot edit this transaction anymore'
            ], 422);
      }
      
    }


// Calling PayThru gateway for transaction response updates
public function webhookGroupResponse(Request $request)
{
 try {
    $productId = env('paythru_group_productid');
    $response = $request->all();
    $dataEncode = json_encode($response);
    $data = json_decode($dataEncode);
    $modelType = "group";

   

    Log::info("Starting webhookGroupResponse");

    if ($data->notificationType == 1) {
        $userGroup = userGroup::where('paymentReference', $data->transactionDetails->paymentReference)->first();
//	$minus_residual = $userGroup->minus_residual;
        if ($userGroup) {
//   	    $existing_minus_residual = $userGroup->minus_residual ?? 0;
  //          $new_minus_residual = $existing_minus_residual + $data->transactionDetails->residualAmount;

            $userGroup->payThruReference = $data->transactionDetails->payThruReference;
            $userGroup->fiName = $data->transactionDetails->fiName;
            $userGroup->status = $data->transactionDetails->status;
            $userGroup->amount = $data->transactionDetails->amount;
            $userGroup->responseCode = $data->transactionDetails->responseCode;
            $userGroup->paymentMethod = $data->transactionDetails->paymentMethod;
            $userGroup->commission = $data->transactionDetails->commission;
            $userGroup->residualAmount = $data->transactionDetails->residualAmount;
            $userGroup->resultCode = $data->transactionDetails->resultCode;
            $userGroup->responseDescription = $data->transactionDetails->responseDescription;
    //        $userGroup->minus_residual = $new_minus_residual;
            $userGroup->save();

	   $activePayment = new Active([
                    'paymentReference' => $data->transactionDetails->paymentReference,
                    'product_id' => $productId,
                    'product_type' => $modelType
                ]);
           $activePayment->save();
                Log::info("Payment reference saved in ActivePayment table");

            Log::info("User Group updated");
        } else {
            Log::info("User Group not found for payment reference: " . $data->transactionDetails->paymentReference);
        }

        http_response_code(200);
		} elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = GroupWithdrawal::where('transactionReferences', $transactionReferences)->first();

                if ($withdrawal) {
                    $uniqueId = $withdrawal->uniqueId;

                    $updatePaybackWithdrawal = GroupWithdrawal::where([
                        'transactionReferences' => $transactionReferences,
                        'uniqueId' => $uniqueId
                    ])->first();

                    if ($updatePaybackWithdrawal) {
                        $updatePaybackWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
                        $updatePaybackWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
			// Set the status to "success"
                        $updatePaybackWithdrawal->status = 'success';
                        $updatePaybackWithdrawal->save();

                        Log::info("Kontribute withdrawal updated");
                    } else {
                        Log::info("Kontribte withdrawal not found for transaction references: " . $transactionReferences);
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


public function AzatGroupCollection(Request $request)
{
    $current_timestamp = now();
    $timestamp = strtotime($current_timestamp);
    $secret = env('PayThru_App_Secret');
    $productId = env('PayThru_expense_productid');
    $hash = hash('sha512', $timestamp . $secret);
    $AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');
    $charges = env('PayThru_Withdrawal_Charges');

    $userGroupTransactions = userGroup::where('reference_id', auth()->user()->id)
        ->sum('residualAmount');

    // Step 1: Subtract residualAmount from the request->amount and update it in minus_residual column
    $requestAmount = $request->amount;
    $minusResidual = $userGroupTransactions - $requestAmount;

    // Check if the first withdrawal request or consecutive withdrawal
    $latestWithdrawal = userGroup::where('reference_id', auth()->user()->id)
        ->latest('updated_at')
        ->first();

    if ($latestWithdrawal) {
        // Consecutive withdrawal request
        $latestMinusResidual = $latestWithdrawal->minus_residual;
        if ($requestAmount > $latestMinusResidual) {
            // Step 4: Request amount exceeds latest minus_residual
            $remainingAmount = $requestAmount - $latestMinusResidual;
            //$remainingMinusResidual = $userGroupTransactions - $remainingAmount;
            if ($remainingAmount < 0) {
                return response()->json(['message' => 'You do not have sufficient amount in your Kontribute'], 400);
            }
            $minusResidual = $remainingAmount;
        } else {
            // Step 3: Update minus_residual for consecutive withdrawal
            $minusResidual = $latestMinusResidual - $requestAmount;
        }
    } else {
        // Step 3: First request to withdraw
        if ($requestAmount > $userGroupTransactions) {
            return response()->json(['message' => 'You do not have sufficient amount in your Kontribute'], 400);
        }
    }

	userGroup::where('reference_id', auth()->user()->id)->update(['minus_residual' => $minusResidual]);


    // Save the withdrawal details
    $withdrawal = new GroupWithdrawal([
        'account_number' => $request->account_number,
        'description' => $request->description,
        'beneficiary_id' => auth()->user()->id,
        'amount' => $requestAmount - $charges,
        'bank' => $request->bank,
        'charges' => $charges,
        'uniqueId' => Str::random(10),
       // 'minus_residual' => $minusResidual, // Update minus_residual here
    ]);

    $withdrawal->save();

    $kontributeAmountWithdrawn = $requestAmount - $charges;
    $acct = $request->account_number;

    $bank = Bank::where('user_id', auth()->user()->id)
        ->where('account_number', $acct)
        ->first();

    if (!$bank) {
        return response()->json(['message' => 'Bank account not found'], 404);
    }

    $beneficiaryReferenceId = $bank->referenceId;

    $token = $this->paythruService->handle();

    $data = [
        'productId' => $productId,
        'amount' => $kontributeAmountWithdrawn,
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

    $collection = $response->object();
    Log::info('API response: ' . json_encode($collection));
    $saveTransactionReference = GroupWithdrawal::where('beneficiary_id', Auth::user()->id)->where('uniqueId', $withdrawal->uniqueId)->update([
        'transactionReferences' => $collection->transactionReference,
        'status' => $collection->message,
       // 'minus_residual' => $minusResidual
    ]);

    return response()->json($saveTransactionReference, 200);
}

        public function countAllGroupsPerUser()
        {
        $getAuthUser = Auth::user();
        $getUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->count();
        return response()->json($getUserGroups);
        }
        
        public function getAllGroupsPerUser()
        {
        $getAuthUser = Auth::user();
        $countUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->get();
        return response()->json($countUserGroups);
        
        }



	public function getWithdrawalTransaction()
{
    $getWithdrawalTransaction = GroupWithdrawal::where('beneficiary_id', Auth::user()->id)->get();
    if ($getWithdrawalTransaction->count() > 0) {
        return response()->json($getWithdrawalTransaction);
    } else {
        return response([
            'message' => 'Transaction not found for this user'
        ], 403);
    }
}

        public function getUserGroup()
    {
            $pageNumber = 50;
            $getAuthUser = Auth::user();
            $getUserGroupAddedTransactions = userGroup::where('email', $getAuthUser->email)->whereNull('deleted_at')->paginate($pageNumber);
$groupIds = DB::table('expenses')
    ->join('user_groups', function ($join) {
        $join->on('expenses.user_id', '=', 'user_groups.reference_id')
            ->on('expenses.id', '=', 'user_groups.group_id');
    })
    ->select('expenses.id')
    ->where('expenses.user_id', '=', $getAuthUser->id)
    ->whereNull('expenses.subcategory_id')
    ->whereNull('expenses.deleted_at') // Exclude soft-deleted records
    ->groupBy('expenses.id')
    ->pluck('expenses.id');

$getUserGroupExpense = DB::table('expenses')
    ->join('user_groups', function ($join) {
        $join->on('expenses.user_id', '=', 'user_groups.reference_id')
            ->on('expenses.id', '=', 'user_groups.group_id');
    })
    ->select('expenses.*', DB::raw('SUM(user_groups.residualAmount) as total_paid'))
    ->whereIn('expenses.id', $groupIds)
    ->whereNull('expenses.deleted_at') // Exclude soft-deleted records
    ->groupBy('expenses.id')
    ->paginate($pageNumber);

            return response()->json([
                'getAuthUserGroupsCreated' => $getUserGroupExpense,
                'getGroupsInvitedTo' => $getUserGroupAddedTransactions,
            ]);
}

        public function getRandomUserGroup($email)
{

        $getUserGroup = userGroup::where('reference_id', Auth::user()->id)->where('email', $email)->first();
        return response()->json($getUserGroup);

}

        public function getAllMemebersOfAGroup($groupId)
{
        $getUserGroup = userGroup::where('reference_id', Auth::user()->id)->where('group_id', $groupId)->select('email')->get();
        return response()->json($getUserGroup);
}


public function getUserAmountsPaidPerGroup(Request $request, $groupId)
    {
        $UserAmountsPaid = userGroup::where('reference_id', Auth::user()->id)->where('group_id', $groupId)->get();
        return response()->json($UserAmountsPaid);
    }



        public function getOneGroupPerUser($id)
        {
// $getAuthUser = Auth::user();
        $get = userGroup::find($id);
        $getUserGroup = UserGroup::where('group_id', $get)->first();
         return response()->json($getUserGroup);

        }
        

        public function deleteInvitedGroupUser($user_id) 
{

        $deleteInvitedExpenseUser = UserGroup::findOrFail($user_id);
        $getDeleteUserGroup = userGroup::where('_id', Auth::user()->id)->where('user_id', $deleteInvitedExpenseUser)->first();
        if($getDeleteUserGroup)
         $getDeleteUserGroup->delete(); 
        // return "done";
        else
        return response()->json(null); 
}

        
        public function deleteGroup($id) 
        {
        //$user = Auth()->user();
        $deleteExpense = expense::findOrFail($id);
        $getDeletedExpense = expense::where('user_id', Auth::user()->id)->where('id', $deleteExpense);
        if($deleteExpense)
        //$userDelete = Expense::where('user', $user)
        $deleteExpense->delete(); 
        else
        return response()->json(null); 
        }

public function reinitiateTransactionToGroup(Request $request, $groupId, $id)
        {
     $group = Expense::findOrFail($groupId);
     $existingUserGroup = userExpense::findOrFail($id);
    // Find the existing UserGroup record with the desired uidd
//    $existingUserGroup = UserGroup::where('reference_id', Auth::user()->id)
  //      ->where('group_id', $groupId)
    //    ->where('id', $id)
      //  ->first();

 if ($existingUserGroup->reference_id !== Auth::user()->id) {
        return response([
            'message' => 'You are not authorized to perform this action.',
        ], 403);
    }



    if (!$existingUserGroup) {
        // If the UserGroup with the desired uidd is not found, return an error response
        return response([
            'message' => 'Invalid uidd. Please provide a valid uidd for the existing transaction.',
        ], 422);
    }

    // Fetch the invited user's first and last name based on their email address
    $invitedUser = Invited::where('email', $existingUserGroup->email)->first();
    $firstName = $invitedUser->first_name ?? 'Unknown';
    $lastName = $invitedUser->last_name ?? '';

    // Calculate the payable amount for the new transaction
    $payable = $existingUserGroup->amount_payable - $existingUserGroup->residualAmount;

$info = UserGroup::create([
                  'reference_id' => Auth::user()->id,
                  'group_id' => $groupId,
                  'name' => $group->name,
                  'uique_code' => $group->uique_code,
                  'email' => $existingUserGroup->email,
		  'first_name' => $firstName,
        	  'last_name' => $lastName,
                  'description' => $group->description,
                  'split_method_id' => $request['split_method_id'],
                  'amount_payable' => $payable,
                  'actualAmount' => $group->actual_amount,
                  'bankName' => $request['bankName'],
                  'account_name' => $request['account_name'],
                  'bankCode' => $request['bankCode'],
                  'account_number' => $request['account_number'],
                 'uidd'=> Str::random(10),
    ]);
              $current_timestamp = now();
              $timestamp = strtotime($current_timestamp);
      
              $productId = env('PayThru_expense_productid');
              $prodUrl = env('PayThru_Base_Live_Url');
      
              $data = [
                  'amount' => $payable,
                  'productId' => $productId,
                  'transactionReference' => time() . $groupId,
                  'paymentDescription' => $group->description,
                  'paymentType' => 1,
                  'sign' => hash('sha512', $payable . env('PayThru_App_Secret')),
                  'displaySummary' => true,
              ];
      //return $data;
              $token = $this->paythruService->handle();
              $url = $prodUrl . '/transaction/create';
              $response = Http::withHeaders([
                  'Content-Type' => 'application/json',
                  'Authorization' => $token,
              ])->post($url, $data);
              if ($response->failed()) {
                  return false;
              } else {
                  $transaction = json_decode($response->body(), true);
                  if (!$transaction['successful']) {
                      return response("Whoops! " . json_encode($transaction), 422);
                  }
                  $paylink = $transaction['payLink'];
                  $slip = ['paylink' => $paylink, 'amount' => $data['amount'], 'receipient' => $existingUserGroup->email];
                  $authmail = Auth::user();
	//	$userss = Invited::where('auth_id', Auth::user()->id)->where('email', $slip['receipient'])->first();
	//	$uxer = $userss->first_name;
                  Mail::to($slip['receipient'])->send(new KontributMail($slip));
                  if ($paylink) {
                      $getLastString = explode('/', $paylink);
                      $now = end($getLastString);
                      UserGroup::where([
              		   'email' => $slip['receipient'],
                          'group_id' => $group->id,
                          'reference_id' => Auth::user()->id,
			  'uidd' => $info->uidd
                      ])->update([
                          'paymentReference' => $now,
                      ]);
                  }
                  return response()->json($transaction);
              }
          }
        }
