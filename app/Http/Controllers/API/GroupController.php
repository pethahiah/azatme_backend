<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\Referrals;
use Illuminate\Http\Request;
use App\Http\Requests\GroupRequest;
use App\Http\Requests\userGroupRequest;
use App\Expense;
use Illuminate\Support\Str;
use Auth;
use App\Bank;
use App\User;
use Mail;
use App\Donor;
use App\OpenActive;
use Carbon\Carbon;
use App\Mail\KontributMail;
use App\UserGroup;
use App\KontributeBalance;
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


    public $referral;
    public $paythruService;

    public function __construct(PaythruService $paythruService, Referrals $referral)
    {
        $this->paythruService = $paythruService;
        $this->referral = $referral;
    }

public function getFundDonor(Request $request, $transactionReference)
{
    $perPage = $request->input('per_page', 20);
    $page = $request->input('page', 1);
    $user = UserGroup::where('merchantReference', $transactionReference)
        ->where('reference_id', Auth::user()->id)
	->orderBy('created_at', 'desc')
	->paginate($perPage, ['*'], 'page', $page)
        ->first();

    if ($user) {
        $transaction = $user->merchantReference;
        $donorModel = Donor::where('transactionReference', $transaction)->get();

        return response()->json(['message' => 'successful', 'data' => $donorModel], 200);
    } else {
        return response()->json(['message' => 'transaction reference not found'], 404);
    }
}

public function getFunds(Request $request)
{
    $user = Auth::user()->id;

    $perPage = $request->input('per_page', 20);
    $page = $request->input('page', 1);

    $funds = UserGroup::where('reference_id', $user)
        ->where('paymentType', 4)
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    if ($funds->count() > 0) {
        return response()->json(['message' => 'successful', 'data' => $funds], 200);
    } else {
        return response()->json(['message' => 'Funds not found'], 404);
    }
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


public function getAllKontributeCreatedt(Request $request)
{
        $auth = Auth::user();
	$perPage = $request->input('per_page', 20);
    	$page = $request->input('page', 1);
        $getKontribute = Expense::where('user_id', $auth->id)->whereNull('category_id')->whereNull('subcategory_id')->where('confirm', 0)->paginate($perPage, ['*'], 'page', $page);
        return response()->json($getKontribute);
}




public function inviteUsersToGroup(Request $request, $groupId)
{
    // Retrieve the group information based on the provided $groupId
    $group = Expense::findOrFail($groupId);

    // Validate the group
    if (!$group) {
        return response([
            'message' => "Id doesn't belong to this transaction category"
        ], 401);
    }

    // Extract necessary inputs from the request and gather other required parameters from environment variables
    $emails = $request->input('email');
    $paymentType = $request->input('paymentType', 1);
    $productId = env('PayThru_kontribute_productid');
    $currentTimestamp = now();
    $timestamp = strtotime($currentTimestamp);
    $secret = env('PayThru_App_Secret');
    $hashSign = hash('sha512', $group->amount . $secret);
    $prodUrl = env('PayThru_Base_Live_Url');

   if ($group) {
    $group->confirm = 1;
    $group->save();
	} else {
    return response()->json([
        'error' => 'Expense not found for group'
    ], 404);
	}

    $token = $this->paythruService->handle();

    if (!$token) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }

    // Initialize variables for payers and total payable amount
    $payers = [];
    $totalPayable = 0;

    // Handle open transaction link
    if (empty($emails) && $paymentType == 4) {
        $inputExpiration = $request->expirationTime;
        $paylinkExpirationTime = Carbon::createFromFormat('Y-m-d H:i:s', $inputExpiration);

        $data = [
            'amount' => $group->amount,
            'productId' => $productId,
            'transactionReference' => time() . $group->id,
            'paymentDescription' => $group->description,
            'paymentType' => 4, // Set paymentType to 4 for open transaction link
            'sign' => $hashSign,
            'expireDateTime' => $paylinkExpirationTime,
            'displaySummary' => false,
        ];

        // Send payment request to PayThru
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post("$prodUrl/transaction/create", $data);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to create open transaction link.'
            ], 500);
        } else {
            $transaction = json_decode($response->body(), true);
            $paylink = $transaction['payLink'];
         //   return $paylink;
            if ($paylink) {
                $getLastString = (explode('/', $paylink));
                $now = end($getLastString);
              //  return $now;
                $info = UserGroup::create([
                    'reference_id' => Auth::user()->id,
                    'group_id' => $group->id,
                    'name' => $group->name,
                    'uique_code' => $group->uique_code,
                    'description' => $group->description,
                    'split_method_id' => $request->input('split_method_id'),
                    'actualAmount' => $group->amount,
                    'bankName' => $request->input('bankName'),
                    'account_name' => $request->input('account_name'),
                    'bankCode' => $request->input('bankCode'),
                    'account_number' => $request->input('account_number'),
                    'paymentType' => $request->input('paymentType'),
                    'merchantReference' => $data['transactionReference'],
                ]);
            }
            return response()->json($transaction);
//return response()->json(['info' => $info, 'transaction' => $transaction]);

        }
    } else {
        // Process user invitations
	$authUser = Auth::user();
if ($emails && $request->consent == true) {
    // Add authenticated user's email to the list
    $emails .= ';' . $authUser->email;
} else {
    // Remove auth_email if it exists in the list
    $emailsArray = explode(';', $emails);
    $emailsArray = array_filter($emailsArray, function ($email) use ($authUser) {
        return $email != $authUser->email;
    });
    $emails = implode(';', $emailsArray);
}
        $emailArray = explode(';', $emails);
        $count = count($emailArray);

        foreach ($emailArray as $key => $em) {
            $user = Invited::where('auth_id', Auth::user()->id)->where('email', $em)->first();
            $payable = 0;


        if($request['split_method_id'] == 3)
        {
            $payable = $group->amount;

        } elseif($request['split_method_id'] == 1)
        {
          if(isset($request->percentage))
          {
            $payable = $group->amount*$request->percentage/100;
          }elseif(isset($request->percentage_per_user))
          {

            $ppu = json_decode($request->percentage_per_user);
            //return $em;

            $payable = $ppu->$em*$group->amount/100;
          }
        }elseif($request['split_method_id'] == 2)
        {
           //$payable = $expense->amount/$count;
            $payable = round(($group->amount / $count), 2);

            if ($key == $count - 1) {
        $payable = round($group->amount - (round($payable, 2) * ($count - 1)), 2);
        //$payable = $group->amount - (round($payable, 2) * ($count - 1));
        }

        }elseif($request['split_method_id'] == 4)
        {
            $payable = $group->amount/$count;
        }

            $info = UserGroup::create([
                'reference_id' => Auth::user()->id,
                'group_id' => $group->id,
                'name' => $group->name,
                'uique_code' => $group->uique_code,
                'email' => $em,
                'description' => $group->description,
                'split_method_id' => $request->input('split_method_id'),
                'amount_payable' => $payable,
                'actualAmount' => $group->amount,
                'bankName' => $request->input('bankName'),
                'account_name' => $request->input('account_name'),
                'bankCode' => $request->input('bankCode'),
                'consent' => $request->input('consent', 0),
//		'first_name' => $user->first_name,
  //          	'last_name' => $user->last_name,
		'first_name' => $user ? $user->first_name : null,
		'last_name' => $user ? $user->last_name : null,
                'account_number' => $request->input('account_number'),
            ]);

            $payers[] = ["payerEmail" => $em, "paymentAmount" => $info->amount_payable];
            $totalPayable += $info->amount_payable;
        }

        $data = [
            'amount' => $group->amount,
            'productId' => $productId,
            'transactionReference' => time() . $group->id,
            'paymentDescription' => $group->description,
            'paymentType' => 1,
            'sign' => $hashSign,
            'displaySummary' => false,
            'splitPayInfo' => [
                'inviteSome' => false,
                'payers' => $payers
            ],
        ];
//return $data;
        $url = $prodUrl;
        $urls = $url . '/transaction/create';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($urls, $data);
//            return $response;
        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to create payment transaction.'
            ], 500);
        } else {
            $transaction = json_decode($response->body(), true);
            $splitResult = $transaction['splitPayResult']['result'];

            foreach ($splitResult as $key => $slip) {
                $uxer = $user->first_name;
                Mail::to($slip['receipient'], $uxer)->send(new KontributMail($slip,$uxer));
                $paylink = $slip['paylink'];

                if ($paylink) {
                    $getLastString = (explode('/', $paylink));
                    $now = end($getLastString);

                    $userGroupReference = UserGroup::where(['email' => $slip['receipient'], 'group_id' => $group->id, 'reference_id' => Auth::user()->id])->update([
                        'paymentReference' => $now,
                    ]);
                }
            }

            return response()->json($transaction);
        }
    }
}


public function getOpenKontributionsById($id, Request $request)
{
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    // Validate the $id parameter to ensure it's a positive integer.
    if (!is_numeric($id) || $id <= 0 || !is_int($id + 0)) {
        return response()->json(['error' => 'Invalid ID'], 400);
    }

    $openKontributionsById = UserGroup::where('reference_id', Auth::id())
        ->where('paymentType', 2)
        ->where('id', $id)
        ->latest()
       ->paginate($perPage, ['*'], 'page', $page);

    return response()->json($openKontributionsById);
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


public function webhookGroupResponse(Request $request)
{
    try {
        $productId = env('paythru_group_productid');
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
        $modelType = "group";
        Log::info("Starting webhookGroupResponse", ['data' => $data, 'modelType' => $modelType]);
        Log::info("Starting webhookGroupResponse");
	 if ($data->notificationType == 1) {
            if (is_null($data->transactionDetails->paymentReference)) {
    // Payment reference is null, check merchantReference
    $userGroup = UserGroup::where('merchantReference', $data->transactionDetails->merchantReference)->first();
    $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType);
                }
    if ($userGroup) {
        // Merchant reference matches, save to donor table
        $donor = new Donor([
            'payThruReference' => $data->transactionDetails->payThruReference,
            'transactionReference' => $data->transactionDetails->merchantReference,
            'fiName' => $data->transactionDetails->fiName,
            'status' => $data->transactionDetails->status,
            'amount' => $data->transactionDetails->amount,
            'responseCode' => $data->transactionDetails->resultCode ?? null,
            'paymentMethod' => $data->transactionDetails->paymentMethod,
            'commission' => $data->transactionDetails->commission,
            'residualAmount' => $data->transactionDetails->residualAmount,
            'responseDescription' => $data->transactionDetails->responseDescription ?? null,
            'providedEmail' => $data->transactionDetails->customerInfo->providedEmail,
            'providedName' => $data->transactionDetails->customerInfo->providedName,
            'remarks' => $data->transactionDetails->customerInfo->remarks ?? null,
        ]);

        // Add donor's residualAmount to userGroup's residualAmount
        $userGroup->residualAmount += $donor->residualAmount;

        $donor->save();
        $userGroup->save();

//        $activeOpenPayment = new OpenActive([
  //          'transactionReference' => $data->transactionDetails->merchantReference,
    //        'product_id' => $productId,
      //      'product_type' => $modelType
       // ]);
       // $activeOpenPayment->save();

        Log::info("Donor saved in Donor table");
        Log::info("User Group updated");
    }
}
	 else {
                // Payment reference is not null, update UserGroup
                $userGroup = UserGroup::where('paymentReference', $data->transactionDetails->paymentReference)->first();
                $referral = ReferralSetting::where('status', 'active')
             ->latest('updated_at')
             ->first();
                if ($referral) {
                $this->referral->checkSettingEnquiry($modelType);
                     }
                if ($userGroup) {
                    $userGroup->payThruReference = $data->transactionDetails->payThruReference;
                    $userGroup->fiName = $data->transactionDetails->fiName;
                    $userGroup->status = $data->transactionDetails->status;
                    $userGroup->amount = $data->transactionDetails->amount;
                    $userGroup->responseCode = $data->transactionDetails->resultCode ?? null;
                    $userGroup->paymentMethod = $data->transactionDetails->paymentMethod;
                    $userGroup->commission = $data->transactionDetails->commission;
		    // Check if residualAmount is negative
			if ($data->transactionDetails->residualAmount < 0) {
    		   $userGroup->negative_amount = $data->transactionDetails->residualAmount;
			} else {
    		    $userGroup->negative_amount = 0;
			}
		    $userGroup->residualAmount = $data->transactionDetails->residualAmount ?? 0;
                    $userGroup->responseDescription = $data->transactionDetails->responseDescription ?? null;
                    $userGroup->providedEmail = $data->transactionDetails->customerInfo->providedEmail;
                    $userGroup->providedName = $data->transactionDetails->customerInfo->providedName;
                    $userGroup->remarks = $data->transactionDetails->customerInfo->remarks ?? null;

                    $userGroup->save();

         //           $activePayment = new Active([
           //             'paymentReference' => $data->transactionDetails->paymentReference,
             //           'product_id' => $productId,
               //         'product_type' => $modelType
                 //   ]);
                   // $activePayment->save();
                  // Log::info("Payment reference saved in ActivePayment table");
                    Log::info("User Group updated");
                }
            }
        } elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = GroupWithdrawal::where('transactionReferences', $transactionReferences)->first();
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType);
                }
                if ($withdrawal) {
                    $uniqueId = $withdrawal->uniqueId;

                    $updatePaybackWithdrawal = GroupWithdrawal::where([
                        'transactionReferences' => $transactionReferences,
                        'uniqueId' => $uniqueId
                    ])->first();

                    if ($updatePaybackWithdrawal) {
                        $updatePaybackWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
                        $updatePaybackWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
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


 public function webhookGroupResponsegddssold(Request $request)
    {
        try {
            $productId = env('paythru_group_productid');
            $response = $request->all();
            $dataEncode = json_encode($response);
            $data = json_decode($dataEncode);
            $modelType = "group";
    	    Log::info("Starting webhookGroupResponse", ['data' => $data, 'modelType' => $modelType]);
            Log::info("Starting webhookGroupResponse");

            if ($data->notificationType == 1) {
                if (is_null($data->transactionDetails->paymentReference)){
                    // Payment reference is null, check merchantReference
                    $userGroup = UserGroup::where('merchantReference', $data->transactionDetails->merchantReference)->first();

                    if ($userGroup) {
                        // Merchant reference matches, save to donor table
                        $donor = new Donor([
                            // Assign the fields from $data->transactionDetails to the corresponding Donor fields
                            'payThruReference' => $data->transactionDetails->payThruReference,
                            'transactionReference' => $data->transactionDetails->merchantReference,
                            'fiName' => $data->transactionDetails->fiName,
                            'status' => $data->transactionDetails->status,
                            'amount' => $data->transactionDetails->amount,
                            'responseCode' => $data->transactionDetails->responseCode,
                            'paymentMethod' => $data->transactionDetails->paymentMethod,
                            'commission' => $data->transactionDetails->commission,
                            'residualAmount' => $data->transactionDetails->residualAmount,
                            'resultCode' => $data->transactionDetails->resultCode,
                            'responseDescription' => $data->transactionDetails->responseDescription,
                            'providedEmail' => $data->transactionDetails->customerInfo->providedEmail,
                            'providedName' => $data->transactionDetails->customerInfo->providedName,
                            'remarks' => $data->transactionDetails->customerInfo->remarks
                        ]);

                        // Add donor's residualAmount to userGroup's residualAmount
                        $userGroup->residualAmount += $donor->residualAmount;

                        $donor->save();
                        $userGroup->save();

                        $activeOpenPayment = new OpenActive([
                            'transactionReference' => $data->transactionDetails->merchantReference, // Set to null since paymentReference is null
                            'product_id' => $productId,
                            'product_type' => $modelType
                        ]);
                        $activeOpenPayment->save();

                        Log::info("Donor saved in Donor table");
                        Log::info("User Group updated");
                    }
                } else {
                    // Payment reference is not null, update UserGroup
                    $userGroup = UserGroup::where('paymentReference', $data->transactionDetails->paymentReference)->first();

                    if ($userGroup) {
                        // Existing user group, update fields
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
                        $userGroup->providedEmail = $data->transactionDetails->customerInfo->providedEmail;
                        $userGroup->providedName = $data->transactionDetails->customerInfo->providedName;
                        $userGroup->remarks = $data->transactionDetails->customerInfo->remarks;

                        $userGroup->save();

                        $activePayment = new Active([
                            'paymentReference' => $data->transactionDetails->paymentReference,
                            'product_id' => $productId,
                            'product_type' => $modelType
                        ]);
                        $activePayment->save();
                        Log::info("Payment reference saved in ActivePayment table");
                        Log::info("User Group updated");
                    }
                }
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
    $productId = env('PayThru_kontribute_productid');
    $hash = hash('sha512', $timestamp . $secret);
    $AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');
    $charges = env('PayThru_Withdrawal_Charges');

    $requestAmount = $request->amount;


//    $latestWithdrawal = KontributeBalance::where('user_id', auth()->user()->id)
  //      ->latest()
    //    ->pluck('balance')
      //  ->first();

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
  $kontributeTransactions = $getUserKontributeTransactions - $kontributeBalance;


    if ($requestAmount < 130) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }


    if ($kontributeTransactions) {
        if ($requestAmount > $kontributeTransactions) {
            return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
        }
        $minusResidual = $kontributeTransactions - $requestAmount;
    }

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
     if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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

   // KontributeBalance::where('user_id', auth()->user()->id)
     //       ->latest()->update(['balance' => $minusResidual]);


   KontributeBalance::create([
        'user_id' => Auth::user()->id,
        'balance' => $minusResidual,
	'action' => 'debit',
    ]);

    // Save the withdrawal details
    $withdrawal = new GroupWithdrawal([
        'account_number' => $request->account_number,
        'description' => $request->description,
        'beneficiary_id' => auth()->user()->id,
        'amount' => $requestAmount - $charges,
        'bank' => $request->bank,
        'charges' => $charges,
        'uniqueId' => Str::random(10),
    ]);

    $withdrawal->save();

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
        ], 404);
    }
}

        public function getUserGroup(Request $request)
    {

            $perPage = $request->input('per_page', 10);
    	    $page = $request->input('page', 1);
            $getAuthUser = Auth::user();
            $getUserGroupAddedTransactions = userGroup::where('email', $getAuthUser->email)->whereNull('deleted_at')->whereNull('merchantReference')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
$groupIds = DB::table('expenses')
    ->join('user_groups', function ($join) {
        $join->on('expenses.user_id', '=', 'user_groups.reference_id')
            ->on('expenses.id', '=', 'user_groups.group_id');
    })
    ->select('expenses.id')
    ->where('expenses.user_id', '=', $getAuthUser->id)
    ->whereNull('expenses.subcategory_id')
    ->whereNull('expenses.deleted_at') // Exclude soft-deleted records
    ->whereNull('user_groups.merchantReference')
    ->groupBy('expenses.id')
    ->pluck('expenses.id');

$getUserGroupExpense = DB::table('expenses')
    ->join('user_groups', function ($join) {
        $join->on('expenses.user_id', '=', 'user_groups.reference_id')
            ->on('expenses.id', '=', 'user_groups.group_id');
    })
    ->select('expenses.*', DB::raw('SUM(user_groups.residualAmount) as total_paid'))
    ->whereIn('expenses.id', $groupIds)
    ->whereNull('user_groups.merchantReference')
    ->whereNull('expenses.deleted_at') // Exclude soft-deleted records
    ->groupBy('expenses.id')
    ->orderBy('created_at', 'desc')
    ->paginate($perPage, ['*'], 'page', $page);

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



public function getOpenKontributions(Request $request)
    {
        $perPage = $request->input('per_page', 10);
    	$page = $request->input('page', 1);;
        $OpenKontributions = userGroup::where('reference_id', Auth::user()->id)->where('paymentType', 4)->latest()->paginate($perPage, ['*'], 'page', $page);
        return response()->json($OpenKontributions);
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
	 if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
