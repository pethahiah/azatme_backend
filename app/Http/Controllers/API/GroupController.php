<?php

namespace App\Http\Controllers\API;

use App\Charge;
use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ChargeService;
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
    public $chargeService;

    public function __construct(PaythruService $paythruService, Referrals $referral, ChargeService $chargeService)
    {
        $this->paythruService = $paythruService;
        $this->referral = $referral;
        $this->chargeService = $chargeService;
    }


    /**
     * Get fund donors by transaction reference.
     *
     * @group Kontribute
     *
     * @get /get-donor/{transactionReference}
     *
     * @param string $transactionReference required The transaction reference to find donors for. Example: txn_123456
     * @queryParam per_page int The number of results per page. Default is 20. Example: 10
     * @queryParam page int The page number for pagination. Default is 1. Example: 2
     *
     * @response 200 {
     *     "message": "successful",
     *     "data": [
     *         {
     *             "id": 1,
     *             "transactionReference": "txn_123456",
     *             "name": "John Doe",
     *             "email": "john.doe@example.com",
     *             "amount": 100.00
     *         }
     *     ]
     * }
     * @response 404 {
     *     "message": "transaction reference not found"
     * }
     */

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

    /**
     * Create a new group.
     *
     * @group Kontribute
     *
     * @post /createGroup
     *
     * @bodyParam name string required The name of the group. Example: Team Outing
     * @bodyParam description string The description of the group. Example: Group expenses for team outing.
     * @bodyParam amount number required The total amount for the group. Example: 500.00
     *
     * @response 200 {
     *     "id": 1,
     *     "name": "Team Outing",
     *     "description": "Group expenses for team outing.",
     *     "amount": 500.00,
     *     "user_id": 1,
     *     "uique_code": "abc123xyz"
     * }
     */

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

    /**
     * Update an existing group.
     *
     * @group Kontribute
     *
     * @put /updateGroup/{id}
     *
     * @param int $id required The ID of the group to update. Example: 1
     * @bodyParam name string The new name of the group. Example: Team Outing Updated
     * @bodyParam description string The new description of the group. Example: Updated group expenses for team outing.
     * @bodyParam amount number The new total amount for the group. Example: 600.00
     *
     * @response 200 {
     *     "id": 1,
     *     "name": "Team Outing Updated",
     *     "description": "Updated group expenses for team outing.",
     *     "amount": 600.00,
     *     "user_id": 1,
     *     "uique_code": "abc123xyz"
     * }
     * @response 422 {
     *     "message": "You dont have edit right over this Kontribute"
     * }
     */

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

    /**
     * Get all draft contributions created by the authenticated user.
     *
     * @group Kontribute
     *
     * @get /get-draft-kontribute
     *
     * @queryParam per_page int The number of results per page. Default is 20. Example: 10
     * @queryParam page int The page number for pagination. Default is 1. Example: 2
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "id": 1,
     *             "name": "Draft Contribution",
     *             "amount": 300.00,
     *             "created_at": "2024-08-01T00:00:00.000000Z",
     *             "confirm": 0
     *         }
     *     ]
     * }
     */
public function getAllKontributeCreatedt(Request $request)
{
        $auth = Auth::user();
	$perPage = $request->input('per_page', 20);
    	$page = $request->input('page', 1);
        $getKontribute = Expense::where('user_id', $auth->id)->whereNull('category_id')->whereNull('subcategory_id')->where('confirm', 0)->paginate($perPage, ['*'], 'page', $page);
        return response()->json($getKontribute);
}


    /**
     * Invite users to a group and handle payment transactions.
     *
     * @group Kontribute
     *
     * @post /inviteUsersToGroup/{groupId}
     *
     * @param int $groupId required The ID of the group to invite users to. Example: 1
     * @bodyParam email string required The emails of the users to invite, separated by ';'. Example: user1@example.com;user2@example.com
     * @bodyParam paymentType int The type of payment. Example: 1
     * @bodyParam expirationTime string The expiration time for the payment link if applicable. Example: 2024-09-01 00:00:00
     * @bodyParam split_method_id int The method used for splitting payments. Example: 1
     * @bodyParam percentage number The percentage for splitting payments if applicable. Example: 50
     * @bodyParam percentage_per_user object JSON object with user-specific percentages. Example: {"user1@example.com": 30, "user2@example.com": 70}
     * @bodyParam consent boolean If consent is required. Example: true
     * @bodyParam bankName string The name of the bank. Example: Bank of Example
     * @bodyParam account_name string The name on the account. Example: John Doe
     * @bodyParam bankCode string The bank code. Example: 123456
     * @bodyParam account_number string The account number. Example: 987654321
     *
     * @response 200 {
     *     "transactionReference": "txn_123456",
     *     "payLink": "https://paythru.com/transaction/abc123"
     * }
     * @response 401 {
     *     "message": "Id doesn't belong to this transaction category"
     * }
     * @response 403 {
     *     "error": "Access denied. You do not have permission to access this resource."
     * }
     * @response 500 {
     *     "error": "Failed to create open transaction link."
     * }
     */

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
//return $data;
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

    /**
     * Get open contributions by ID.
     *
     * @group Kontribute
     *
     * @get /get-openLink-transactions-by-id/{id}
     *
     * @param int $id required The ID of the open contribution to retrieve. Example: 1
     * @queryParam per_page int The number of results per page. Default is 10. Example: 5
     * @queryParam page int The page number for pagination. Default is 1. Example: 2
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "id": 1,
     *             "amount": 100.00,
     *             "description": "Contribution for project X",
     *             "created_at": "2024-08-01T00:00:00.000000Z"
     *         }
     *     ]
     * }
     * @response 400 {
     *     "error": "Invalid ID"
     * }
     */
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

        Log::info("Donor saved in Donor table");
        Log::info("User Group updated");
    }
}
	 else {
                // Payment reference is not null, update UserGroup
                $userGroup = UserGroup::where('paymentReference', $data->transactionDetails->paymentReference)->first();

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
                    Log::info("User Group updated");
                }
            }
        } elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = GroupWithdrawal::where('transactionReferences', $transactionReferences)->first();
		$product_action = "withdrawal";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                 if ($referral) {
                    Log::info("Active referral setting found. Checking settings and awarding points if applicable.");
                    $this->referral->checkSettingEnquiry($modelType, $product_action,$transactionReferences);
                } else {
                    Log::warning("No active referral setting found.");
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


    $latestCharge = Charge::orderBy('updated_at', 'desc')->first();
    $applyCharges = $this->chargeService->applyCharges($latestCharge);




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


    if ($requestAmount < 100) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }


    if ($kontributeTransactions) {
        if ($requestAmount > $kontributeTransactions) {
            return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
        }
        $minusResidual = $kontributeTransactions - $requestAmount;
    }

    $kontributeAmountWithdrawn = $requestAmount - $latestCharge->charges;

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

    if ($applyCharges) {
        // Save the withdrawal details with charges
        $withdrawal = new GroupWithdrawal([
            'account_number' => $request->account_number,
            'description' => $request->description,
            'beneficiary_id' => auth()->user()->id,
            'amount' => $requestAmount - $latestCharge->charges,
            'bank' => $request->bank,
            'charges' => $latestCharge->charges,
            'uniqueId' => Str::random(10),
        ]);
    } else {
        // Save the withdrawal details without charges
        $withdrawal = new GroupWithdrawal([
            'account_number' => $request->account_number,
            'description' => $request->description,
            'beneficiary_id' => auth()->user()->id,
            'amount' => $requestAmount,
            'bank' => $request->bank,
            'uniqueId' => Str::random(10),
        ]);
    }

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

    /**
     * Count all groups per user.
     * @group Kontribute
     * @response 200 {
     *   "count": 5
     * }
     *
     * @get /countAllGroupsPerUser
     */

        public function countAllGroupsPerUser()
        {
        $getAuthUser = Auth::user();
        $getUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->count();
        return response()->json($getUserGroups);
        }

    /**
     * Get all groups for the authenticated user.
     * @group Kontribute
     * @response 200 [
     *   {
     *     "id": 1,
     *     "name": "My Group",
     *     "description": "This is a sample group."
     *   }
     * ]
     *
     * @get /getAllGroupsPerUser
     */
        public function getAllGroupsPerUser()
        {
        $getAuthUser = Auth::user();
        $countUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->get();
        return response()->json($countUserGroups);

        }


public function getWithdrawalTransaction(Request $request)
{
//    $page = $request->query('page', 1);

  //  $perPage = 10;
    // Retrieve paginated transactions
    $getWithdrawalTransaction = GroupWithdrawal::where('beneficiary_id', Auth::user()->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Check if there are any items
    if ($getWithdrawalTransaction->isNotEmpty()) {
        return response()->json($getWithdrawalTransaction);
    } else {
        return response()->json([
            'message' => 'Transaction not found for this user'
        ], 404);
    }
}

    /**
     * Get the user group for the authenticated user.
     * @group Kontribute
     * @response 200 {
     *   "group": {
     *     "id": 1,
     *     "name": "My Group"
     *   }
     * }
     *
     * @get /getUserGroup
     */

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

    /**
     * Get a random user group by email.
     * @group Kontribute
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "group": {
     *     "id": 1,
     *     "name": "My Group"
     *   }
     * }
     * @response 404 {
     *   "message": "Group not found"
     * }
     *
     * @get /getRandomUserGroup/{email}
     */

        public function getRandomUserGroup($email)
{

        $getUserGroup = userGroup::where('reference_id', Auth::user()->id)->where('email', $email)->first();
        return response()->json($getUserGroup);

}

    /**
     * Get all members of a group.
     * @group Kontribute
     * @urlParam groupId int required The ID of the group. Example: 1
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "name": "User Name",
     *     "email": "user@example.com"
     *   }
     * ]
     * @response 404 {
     *   "message": "Group not found"
     * }
     *
     * @get /getAllMemebersOfAGroup/{groupId}
     */

        public function getAllMemebersOfAGroup($groupId)
{
        $getUserGroup = userGroup::where('reference_id', Auth::user()->id)->where('group_id', $groupId)->select('email')->get();
        return response()->json($getUserGroup);
}

    /**
     * Get amounts paid per group.
     * @group Kontribute
     * @urlParam groupId int required The ID of the group. Example: 1
     *
     * @response 200 [
     *   {
     *     "user_id": 1,
     *     "amount_paid": 100
     *   }
     * ]
     * @response 404 {
     *   "message": "Group not found"
     * }
     *
     * @get /list-users-per-Group/{groupId}
     */
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

    /**
     * Delete an invited user from a group.
     * @group Kontribute
     * @urlParam user_id int required The ID of the user to remove. Example: 2
     *
     * @response 200 {
     *   "message": "User removed successfully"
     * }
     * @response 404 {
     *   "message": "User or group not found"
     * }
     *
     * @delete /deleteInvitedGoupUser/{user_id}
     */
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

    /**
     * Delete a group.
     * @group Kontribute
     * @urlParam id int required The ID of the group to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Group deleted successfully"
     * }
     * @response 404 {
     *   "message": "Group not found"
     * }
     *
     * @delete /deleteGroup/{id}
     */
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
