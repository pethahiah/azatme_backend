<?php

namespace App\Http\Controllers\API;

use App\Charge;
use App\Http\Controllers\Controller;
use App\Referral;
use App\Services\Referrals;
use App\ReferralSetting;
use Illuminate\Http\Request;
use App\Expense;
use App\User;
use App\Verifysms;
use App\userExpense;
use App\Withdrawal;
use App\UserGroup;
use App\Bank;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendUserInviteMail;
use App\Http\Requests\ExpenseRequest;
use App\Http\Requests\userExpenseRequest;
use Illuminate\Support\Str;
use App\Jobs\ProcessBulkExcel;
use Illuminate\Http\Response;
use App\Helper\Reply;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;
use Datetime;
use App\Exports\ExpenseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;
use App\Setting;
use App\Invited;
use App\Active;
use Storage;
use App\Services\PaythruService;
use Illuminate\Database\QueryException;
use App\Services\ChargeService;



class ExpenseController extends Controller
{
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
     * @group RefundMe
     *
     * API endpoints for user expense management.
     * Create a new expense.
     *
     * This endpoint allows authenticated users to create a new expense.
     *
     * @bodyParam name string required The name of the expense. Example: "Office Supplies"
     * @bodyParam description string optional A description of the expense. Example: "Printer ink and paper"
     * @bodyParam category_id integer required The ID of the expense category. Example: 1
     * @bodyParam subcategory_id integer optional The ID of the expense subcategory. Example: 2
     * @bodyParam actual_amount number required The actual amount spent. Example: 100.00
     * @bodyParam amount number required The reported amount of the expense. Example: 100.00
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Office Supplies",
     *   "description": "Printer ink and paper",
     *   "uique_code": "abcd1234ef",
     *   "category_id": 1,
     *   "subcategory_id": 2,
     *   "actual_amount": 100.00,
     *   "amount": 100.00,
     *   "user_id": 1,
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required."],
     *     "category_id": ["The category_id field is required."],
     *     "actual_amount": ["The actual_amount field is required."],
     *     "amount": ["The amount field is required."]
     *   }
     * }
     *
     * @post /createExpense
     */


public function createExpense(ExpenseRequest $request)
    {

        $expense = Expense::create([

       'name' => $request->name,
            'description' => $request->description,
            'uique_code' => Str::random(10),
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'actual_amount' => $request->actual_amount,
            'amount' => $request->amount,
            'user_id' => Auth::user()->id
        ]);


        return response()->json($expense);

    }

public function add(Request $request)
{

//	return response()->json($request->input());
	$auth = Auth::user();
        // Check if a user with the given auth id already exists
        $existingUser = Invited::where('auth_id', $auth->id)->where('email', $request->email)->first();

        if ($existingUser) {
            return response([
                'message' => 'Invited user already exit with this authourized user'
            ], 409);
        }

        $invitedUser = new Invited([
            'type' => $request->type,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'auth_id' => Auth::user()->id,
        ]);
	$invitedUser->save();
        return response()->json($invitedUser);

}

    /**
     * Get all invited users for the authenticated user.
     * @group RefundMe
     * This endpoint retrieves a list of users invited by the authenticated user.
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "type": "participant",
     *     "email": "example@example.com",
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "auth_id": 1,
     *     "created_at": "2024-08-20T12:00:00.000000Z",
     *     "updated_at": "2024-08-20T12:00:00.000000Z"
     *   }
     *   // More invited user objects
     * ]
     *
     * @response 404 {
     *   "message": "No invited users found."
     * }
     *
     * @get /get-invited-users
     */

public function getInvitedUsers()
{
	$auth = Auth::user();
	$getAllInvited = Invited::where('auth_id', $auth->id)->get();
        return response()->json($getAllInvited);
}

    /**
     * Get all refund requests created by the authenticated user.
     * @group RefundMe
     * This endpoint retrieves all refund requests created by the authenticated user.
     *
     * @queryParam per_page integer optional Number of items per page. Defaults to 10. Example: 10
     * @queryParam page integer optional The page number to retrieve. Defaults to 1. Example: 1
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Office Supplies",
     *       "amount": 100.00,
     *       "status": "pending",
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More refund request objects
     *   ],
     *   "first_page_url": "http://example.com/api/get-draft-refundme?page=1",
     *   "last_page_url": "http://example.com/api/get-draft-refundme?page=2",
     *   "total": 20
     * }
     *
     * @response 404 {
     *   "message": "No refund requests found."
     * }
     *
     * @get /get-draft-refundme
     */

public function getAllRefundMeCreatedt(Request $request)
{
        $auth = Auth::user();
	$perPage = $request->input('per_page', 10);
    	$page = $request->input('page', 1);
        $getRefund = Expense::where('user_id', $auth->id)->whereNotNull('category_id')->whereNotNull('subcategory_id')->where('confirm', 0)->paginate($perPage, ['*'], 'page', $page);
        return response()->json($getRefund);
}


    /**
     * Invite a user to contribute to an expense.
     * @group RefundMe
     * This endpoint allows authenticated users to invite others to contribute to an expense.
     *
     * @urlParam expenseUniqueCode string required The unique code of the expense. Example: "abcd1234ef"
     * @bodyParam email string required The email address of the invited user. Example: "example@example.com"
     * @bodyParam split_method_id integer required The method used to split the expense. Example: 1
     * @bodyParam percentage integer optional Percentage of the expense amount to be contributed by the user. Example: 25
     * @bodyParam percentage_per_user json optional JSON object with percentage split for each user. Example: {"user1@example.com": 50, "user2@example.com": 50}
     * @bodyParam bankName string optional The name of the bank. Example: "Bank of Example"
     * @bodyParam account_name string optional The account name. Example: "John Doe"
     * @bodyParam bankCode string optional The bank code. Example: "123456"
     * @bodyParam account_number string optional The account number. Example: "1234567890"
     *
     * @response 200 {
     *   "transactionId": 1,
     *   "payLink": "https://apps.paythru.ng/services/cardfree/pay/1234567890",
     *   "message": "Invite sent successfully."
     * }
     *
     * @response 400 {
     *   "message": "Whoops! Error message."
     * }
     *
     * @response 403 {
     *   "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @post /userExpense/{expenseUniqueCode}
     */

    public function inviteUserToExpense(Request $request, $expenseUniqueCode)
    {
      //return response()->json($request->input());
       $expense = expense::findOrFail($expenseUniqueCode);
       $input['email'] = $request->input('email');
       $ProductId = env('PayThru_expense_productid');
       $current_timestamp= now();
       $timestamp = strtotime($current_timestamp);
       $secret = env('PayThru_App_Secret');
       $hash = hash('sha512', $timestamp . $secret);
       $amt = $expense->amount;
       $hashSign = hash('sha512', $amt . $secret);
       $PayThru_AppId = env('PayThru_ApplicationId');
//       $prodUrl = env('igreeSandbox');
       $prodUrl = env('PayThru_Base_Live_Url');
       if ($expense) {
    	$expense->confirm = 1;
      	$expense->save();
	} else {
    	Log::info('confim in expense mot updated.');
	}

       $token = $this->paythruService->handle();
        if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
       $emails = $request->email;
       //return $emails;
       if($emails)
       {
       $emailArray = (explode(';', $emails));
       $count = count($emailArray);
       $payers = [];
       $totalpayable = 0;
       foreach ($emailArray as $key => $em) {
           //process each user here as each iteration gives you each email
	 $user = Invited::where('auth_id', Auth::user()->id)->where('email', $em)->first();
         $payable = 0;

        if($request['split_method_id'] == 3)
        {
            $payable = $expense->amount;

        } elseif($request['split_method_id'] == 1)
        {
          if(isset($request->percentage))
          {
            $payable = $expense->amount*$request->percentage/100;
          }elseif(isset($request->percentage_per_user))
          {

            $ppu = json_decode($request->percentage_per_user);
            //return $em;

            $payable = $ppu->$em*$expense->amount/100;
          }
        }elseif($request['split_method_id'] == 2)
        {
           //$payable = $expense->amount/$count;
            $payable = round(($expense->amount / $count), 2);

            if ($key == $count - 1) {
//        $payable = $expense->amount - (round($payable, 2) * ($count - 1));
	  $payable = round($expense->amount - (round($payable, 2) * ($count - 1)), 2);
        }

        }elseif($request['split_method_id'] == 4)
        {
            $payable = $expense->amount/$count;
        }

   $paylink_expiration_time = Carbon::now()->addHours(23);
   $userExpense = userExpense::where('principal_id', Auth::user()->id)
      ->where('expense_id', $expenseUniqueCode)
     ->whereNull('paymentReference')
      ->selectRaw('SUM(payable) AS checkAmountPayable, SUM(residualAmount) AS totalResidual')
      ->first();

   $checkAmountPayable = $userExpense->checkAmountPayable;
   $totalResidual = $userExpense->totalResidual;

  if ($expense->amount == $checkAmountPayable) {
      if ($totalResidual == $expense->amount) {
          // Payment is completely paid
          return response([
              'message' => 'Your payment is already completed.'
          ], 422);
      } else {
          // Refundme is completed
          return response([
              'message' => 'You cannot request for an amount greater than your refundMe. Refundme is completed.'
          ], 422);
      }
  }

  // return  $response()->json("got here");

    $info = userExpense::create([
            'principal_id' => Auth::user()->id,
            'expense_id' => $expense->id,
            'name' => $expense->name,
            'uique_code' => $expense->uique_code,
            'email' => $em,
            'description' => $expense['description'],
            'split_method_id' => $request['split_method_id'],
            'payable' => $payable,
            'first_name'=> "olu",
	        'last_name' => "mide",
            'actualAmount' => $expense->actual_amount,
            'bankName' => $request['bankName'],
            'account_name' => $request['account_name'],
            'bankCode' => $request['bankCode'],
            'account_number' => $request['account_number'],
//	    'uidd'=> Str::random(10),
          ]);
          //return $info

         $payers[] =  ["payerEmail" => $em, "paymentAmount" => $info->payable, "payerName" => "olu"];
         $totalpayable = $totalpayable + $info->payable;

      }

      // Send payment request to paythru

      $data = [
        'amount' => $expense->amount,
        'productId' => $ProductId,
        'transactionReference' => time().$expense->id,
        'paymentDescription' => $expense->description,
        'paymentType' => 1,
        'sign' => $hashSign,
       // 'expireDateTime'=> $paylink_expiration_time,
        'displaySummary' => true,
        // 'splitPayInfo' => [
        //     'inviteSome' => false,
        //     'payers' => $payers
        //   ]

        ];

        if($count > 1)
        {
            $data['splitPayInfo'] = [
              'inviteSome' => false,
              'payers' => $payers
            ];
        }

      //return $data;
         $url = $prodUrl;
        $urls = $url.'/transaction/create';

       $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
      ])->post($urls, $data );
     // return $response->body();
      if($response->failed())
      {
        return false;
      }else{
        $transaction = json_decode($response->body(), true);
        if(!$transaction['successful'])
        {

         // return "Whoops! ". json_encode($transaction);
	return response()->json(['message' => 'Whoops! ' . json_encode($transaction['message'])], 400);
        }

        if($count > 1)
        {
          $splitResult = $transaction['splitPayResult']['result'];
          foreach($splitResult as $key => $slip)
          {
	$userss = Invited::where('auth_id', Auth::user()->id)->where('email', $slip['receipient'])->first();
            $authmail = Auth::user();
	     $uxer = $userss->first_name;
//            Mail::to($slip['receipient'], $authmail['name'])->send(new SendUserInviteMail($slip, $authmail));
 	    Mail::to($slip['receipient'], $authmail['name'], $uxer)->send(new SendUserInviteMail($slip, $authmail, $uxer));
             $paylink = $slip['paylink'];
         // return $paylink;
              if($paylink)
              {
                $getLastString = (explode('/', $paylink));
                $now = end($getLastString);
                //return $now;
          $userExpenseReference = userExpense::where(['email' => $slip['receipient'], 'expense_id' => $expense->id, 'principal_id' => Auth::user()->id])->update([
              'paymentReference' => $now,
          ]);

              }
          }
        }
        else{
         // {"successIndicator":"9nbau1894ef9dg5q","payLink":"https://apps.paythru.ng/services/cardfree/pay/683112951682882400","bankTransferInstruction":null,"splitPayResult":{"isMultiple":true,"result":[{"paylink":"https://apps.paythru.ng/services/cardfree/pay/661361651682882400","amount":10000.0,"receipient":"lumiged4u@gmail.com","isActive":true,"bankPaymentDetails":null,"emvCode":null},{"paylink":"https://apps.paythru.ng/services/cardfree/pay/665979241682882400","amount":10000.0,"receipient":"sunday4oged@yahoo.com","isActive":true,"bankPaymentDetails":null,"emvCode":null}]},"emvCode":null,"code":0,"message":"Successful","data":null,"successful":true

         $paylink = $transaction['payLink'];
         $slip = ['paylink'=> $paylink, 'amount'=> $data['amount'], 'receipient' => $request->email ];


           $userss = Invited::where('auth_id', Auth::user()->id)->where('email', $slip['receipient'])->first();

            $authmail = Auth::user();

	    $uxer = $userss->first_name;
           //   Mail::to($slip['receipient'], $authmail['name'], $InvitedUserName['first_name'])->send(new SendUserInviteMail($slip, $authmail, $InvitedUserName));
            Mail::to($slip['receipient'], $authmail['name'], $uxer)->send(new SendUserInviteMail($slip, $authmail, $uxer));
              if($paylink)
              {
                $getLastString = (explode('/', $paylink));
                $now = end($getLastString);
                //return $now;
          $userExpenseReference = userExpense::where(['email' => $slip['receipient'], 'expense_id' => $expense->id,'uidd' => $info->uidd,'principal_id' => Auth::user()->id])->update([
              'paymentReference' => $now,
//return response()->json($slip);
          ]);

              }

        }
      }
      return response()->json($transaction);

    }
    }

    /**
     * Update a transaction request.
     * @group RefundMe
     * This endpoint allows updating the details of a specific transaction request.
     *
     * @urlParam transactionId integer required The ID of the transaction request to update. Example: 1
     * @bodyParam email string required The new email address for the transaction. Example: "newemail@example.com"
     *
     * @response 200 {
     *   "message": "successful"
     * }
     *
     * @response 422 {
     *   "message": "You cannot edit this transaction anymore"
     * }
     *
     * @put /update-payback-transaction/{transactionId}
     */

    public function UpdateTransactionRequest(Request $request, $transactionId)
    {
      $transaction = userExpense::findOrFail($transactionId);
      //return $transaction;
      if($transaction->status == null)
      {
        $updateTransaction = $request->all();
        $update = userExpense::where('id', $transactionId)->update([
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

    /**
     * Handle incoming webhook notifications for payments and withdrawals.
     *
     * @group RefundMe
     *
     * @bodyParam notificationType integer The type of notification: 1 for payment, 2 for withdrawal. Example: 1
     * @bodyParam transactionDetails object Details of the transaction. See below for detailed structure.
     *
     * @response 200 {
     *     "message": "Request processed successfully."
     * }
     * @response 500 {
     *     "error": "An error occurred"
     * }
     *
     * @responseField notificationType integer The type of notification.
     * @responseField transactionDetails object The details of the transaction, including:
     *     @responseField transactionDetails.paymentReference string The payment reference.
     *     @responseField transactionDetails.payThruReference string The payThru reference.
     *     @responseField transactionDetails.fiName string The financial institution name.
     *     @responseField transactionDetails.status string The status of the transaction.
     *     @responseField transactionDetails.amount number The amount of the transaction.
     *     @responseField transactionDetails.responseCode string The response code from the transaction.
     *     @responseField transactionDetails.paymentMethod string The method of payment.
     *     @responseField transactionDetails.commission number The commission amount.
     *     @responseField transactionDetails.residualAmount number The residual amount.
     *     @responseField transactionDetails.resultCode string The result code of the transaction.
     *     @responseField transactionDetails.responseDescription string The response description.
     *     @responseField transactionDetails.customerInfo object The customer information:
     *         @responseField customerInfo.providedEmail string The email provided by the customer.
     *         @responseField customerInfo.providedName string The name provided by the customer.
     *         @responseField customerInfo.remarks string Any remarks provided by the customer.
     */
public function webhookExpenseResponse(Request $request)
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
            $userExpense = userExpense::where('paymentReference', $data->transactionDetails->paymentReference)->first();
//	    $minus_residual  =  $userExpense->minus_residual;
            if ($userExpense) {
                // Update user expense
//		$existing_minus_residual = $userExpense->minus_residual ?? 0;
  //          	$new_minus_residual = $existing_minus_residual + $data->transactionDetails->residualAmount;

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
		$referral = ReferralSetting::where('status', 'active')->latest('updated_at')->first();

                 if ($referral) {
                    Log::info("Active referral setting found. Checking settings and awarding points if applicable.");
                    $this->referral->checkSettingEnquiry($modelType, $product_action,$transactionReferences);
                } else {
                    Log::warning("No active referral setting found.");
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



  private function userEmailToId($email){
      return User::select('id')->where('email',$email)->first()->value('id');
  }
    /**
     * Retrieve all withdrawal transactions for the authenticated user.
     * @group RefundMe
     * @group Transactions
     *
     *
     * @response 200 {
     *     "id": 1,
     *     "beneficiary_id": 1,
     *     "transactionReferences": "abc123",
     *     "paymentAmount": 100.00,
     *     "recordDateTime": "2024-08-20T12:00:00",
     *     "status": "success"
     * }
     * @response 404 {
     *     "message": "Transaction not found for this user"
     * }
     */

public function getExpenseWithdrawalTransaction(Request $request)
{
//    $page = $request->query('page', 1);

    // Define the number of items per page
  //  $perPage = 10;

    // Retrieve paginated transactions
    $getWithdrawalTransaction = Withdrawal::where('beneficiary_id', Auth::user()->id)
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
     * Retrieve user expenses and transactions the user has been invited to.
     *
     * @group RefundMe
     *
     * @queryParam per_page int Number of items per page. Defaults to 10. Example: 10
     * @queryParam page int Page number for pagination. Defaults to 1. Example: 1
     *
     * @response 200 {
     *     "getAuthUserExpensesCreated": [
     *         {
     *             "id": 1,
     *             "amount": 500.00,
     *             "created_at": "2024-08-20T12:00:00",
     *             "total_paid": 250.00
     *         }
     *     ],
     *     "getExpensesInvitedTo": [
     *         {
     *             "id": 1,
     *             "amount": 1000.00,
     *             "created_at": "2024-08-20T12:00:00"
     *         }
     *     ]
     * }
     */

public function getUserExpense(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);
    $getAuthUser = Auth::user();
    $expenseIds = DB::table('expenses')
        ->join('user_expenses', function ($join) {
            $join->on('expenses.user_id', '=', 'user_expenses.principal_id')
                ->on('expenses.id', '=', 'user_expenses.expense_id');
        })
        ->select('expenses.id')
        ->where('expenses.user_id', '=', $getAuthUser->id)
        ->whereNotNull('expenses.subcategory_id')
        ->whereNull('expenses.deleted_at')
        ->where('expenses.confirm', '=', 1)
        ->groupBy('expenses.id')
        ->pluck('expenses.id');

    $getUserExpense = DB::table('expenses')
        ->join('user_expenses', function ($join) {
            $join->on('expenses.user_id', '=', 'user_expenses.principal_id')
                ->on('expenses.id', '=', 'user_expenses.expense_id');
        })
        ->select('expenses.*', DB::raw('SUM(user_expenses.residualAmount) as total_paid'))
        ->whereIn('expenses.id', $expenseIds)
        ->whereNull('expenses.deleted_at')
        ->where('expenses.confirm', '=', 1)
        ->groupBy('expenses.id')
	->orderBy('expenses.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    $getUserExpenseAddedTransactions = UserExpense::where('email', $getAuthUser->email)
        ->whereNull('deleted_at')
	->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'getAuthUserExpensesCreated' => $getUserExpense,
        'getExpensesInvitedTo' => $getUserExpenseAddedTransactions,
    ]);
}


    /**
     * Retrieve all expenses for the authenticated user.
     *
     * @group RefundMe
     *
     * @queryParam per_page int Number of items per page. Defaults to 10. Example: 10
     * @queryParam page int Page number for pagination. Defaults to 1. Example: 1
     *
     * @response 200 {
     *     "id": 1,
     *     "amount": 500.00,
     *     "created_at": "2024-08-20T12:00:00"
     * }
     */
public function allExpensesPerUser(Request $request)
{

  $perPage = $request->input('per_page', 10);
  $page = $request->input('page', 1);
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->latest()->paginate($perPage, ['*'], 'page', $page);
  return response()->json($getUserExpenses);

}

    /**
     * Retrieve user expenses for a specific email address.
     *
     * @group RefundMe
     *
     * @urlParam email string required The email address of the user.
     * The email address of the user.
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "amount": 500.00,
     *         "created_at": "2024-08-20T12:00:00"
     *     }
     * ]
     */

public function getRandomUserExpense($email)
{
$getUserExpense = userExpense::where('principal_id', Auth::user()->id)->where('email', $email)->get();
return response()->json($getUserExpense);

}

    /**
     * Retrieve all expenses in the system (Admin only).
     *
     * @group RefundMe
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "amount": 500.00,
     *         "created_at": "2024-08-20T12:00:00"
     *     }
     * ]
     * @response 403 {
     *     "message": "Auth user is not an admin"
     * }
     */
public function getAllExpenses()
{
  $getAdmin = Auth::user();
  $getAd = $getAdmin -> usertype;
  if($getAd === 'admin')
  {
  $getAllExpenses = UserExpense::all();
  return response()->json($getAllExpenses);
}
else{
   return response()->json('Auth user is not an admin');
}
}

    /**
     * Count the number of expenses for the authenticated user.
     *
     * @group RefundMe
     *
     * @response 200 {
     *     "count": 10
     * }
     */

public function countExpensesPerUser()
{
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->count();
  return response()->json($getUserExpenses);
}

    /**
     * Update an expense if the authenticated user is the owner.
     *
     * @group RefundMe
     *
     * @param int $id The ID of the expense to update.
     *
     *
     * @response 200 {
     *     "id": 1,
     *     "amount": 500.00,
     *     "updated_at": "2024-08-20T12:00:00"
     * }
     * @response 422 {
     *     "message": "You don't have edit rights over this expense"
     * }
     * @response 404 {
     *     "error": "Expense not found"
     * }
     */
public function updateExpense(Request $request, $id)
{
    $user = Auth::user()->id;
    $get = Expense::where('id', $id)->first();
    $now = $get->user_id;
     //$getAmount = userExpense::where('id', $id)->first();
   // return $user;
    if($now != $user)
    {
         return response()->json(['You dont have edit right over this expense'], 422);


    }else{
        $update = Expense::find($id);
        $update->update($request->all());
        return response()->json($update);
}
}

public function deleteInvitedExpenseUser($user_id)
{

$deleteInvitedExpenseUser = userExpense::findOrFail($user_id);
if($deleteInvitedExpenseUser)
   $deleteInvitedExpenseUser->delete();
else
return response()->json(null);
}

public function deleteExpense($id)
        {
        $deleteExpense = expense::findOrFail($id);
        $getDeletedExpense = expense::where('user_id', Auth::user()->id)->where('id', $deleteExpense);
        if($deleteExpense)
        $deleteExpense->delete();
        else
        return response()->json(null);
        }

public function BulkUploadInviteUsersToExpense(Request $request, $expenseId)
    {

        $expense = expense::findOrFail($expenseId);
        $request->validate([
          'file' => 'required|file'
        ]);
        $file = $request->file('file');
        $extension = $file->extension();
        $file_name = 'user_to_expense_' . time() . '.' . $extension;
        $file->storeAs(
            'excel bulk import', $file_name
        );
        $auth_user_id = Auth::user()->id;
        $result = ProcessBulkExcel::dispatchNow($file_name, $expense, $auth_user_id);
       // dd($result);
        if ($result) {
            $message = "Excel record is been uploaded";
            return response()->json($message);
        } else {
            $message = "Try file upload again";
            return response()->json($message);
        }
    }

public function exportExpenseToExcel(Request $request)
    {
      $fileName = 'azatme_report'.'_'.Carbon::now() . '.' . 'xlsx';
      $userExpense = userExpense::getuserExpense($request);
	return $userExpense;

      Log::info($userExpense);
      ob_end_clean();
      return Excel::download(new ExpenseExport($userExpense), $fileName);
    }

    public function exportExpenseToCsv(Request $request)
    {
      $fileName = 'azatme_report'.'_'.Carbon::now() . '.' . 'csv';
      $userExpense = userExpense::getuserExpense($request);
      Log::info($userExpense);
return $userExpense;
      ob_end_clean();
      return Excel::download(new ExpenseExport($userExpense), $fileName);
    }


    /**
     * Process an individual collection request.
     *
     * @group RefundMe
     *
     * @bodyParam amount number required The amount to withdraw. Example: 150.00
     * @bodyParam account_number string required The bank account number to withdraw to. Example: "1234567890"
     * @bodyParam description string The description of the withdrawal. Example: "Refund"
     * @bodyParam bank string The bank name. Example: "Example Bank"
     *
     * @response 200 {
     *     "transactionReferences": "TRX123456",
     *     "status": "Success"
     * }
     * @response 400 {
     *     "message": "You cannot withdraw an amount less than 100 after commission"
     * }
     * @response 404 {
     *     "message": "Bank account not found"
     * }
     * @response 403 {
     *     "error": "Access denied. You do not have permission to access this resource."
     * }
     * @response 500 {
     *     "message": "Settlement request failed"
     * }
     *
     * @post /collection
     */

    public function AzatIndividualCollection(Request $request)
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

        $latestCharge = Charge::orderBy('updated_at', 'desc')->first();

        $applyCharges = false;

        if ($latestCharge) {
            $applyCharges = $this->chargeService->applyCharges($latestCharge);
        }

        $latestWithdrawal = UserExpense::where('principal_id', auth()->user()->id)
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

        $refundmeAmountWithdrawn = $requestAmount - $latestCharge->charges;

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

	// Log the data
	Log::info('payload data: ' . json_encode($data));

        $url = $prodUrl . '/transaction/settlement';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Settlement request failed'], 500);
        }

        UserExpense::where('principal_id', auth()->user()->id)->where('stat', 1)
            ->latest()->update(['minus_residual' => $minusResidual]);

        $withdrawal = new Withdrawal([
            'account_number' => $request->account_number,
            'description' => $request->description,
            'beneficiary_id' => auth()->user()->id,
            'amount' => $refundmeAmountWithdrawn,
            'bank' => $request->bank,
            'charges' => $applyCharges ? $latestCharge->charges : 0,
            'uniqueId' => Str::random(10),
        ]);

        $withdrawal->save();

        $collection = $response->json();

        Log::info('API response: ' . json_encode($collection));
        $saveTransactionReference = Withdrawal::where('beneficiary_id', Auth::user()->id)
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

    /**
     * Get all members of a specific expense.
     *
     * @group RefundMe
     *
     * @urlParam expenseId int required The ID of the expense. Example: 1
     *
     * @response 200 [
     *     {
     *         "email": "user@example.com"
     *     },
     *     {
     *         "email": "anotheruser@example.com"
     *     }
     * ]
     * @response 404 {
     *     "message": "Expense not found"
     * }
     *
     * @get /getAllMemebersOfAnExpense/{expenseId}
     */

    public function getAllMemebersOfAnExpense($expenseId)
{
        $getExpenseMember = userExpense::where('principal_id', Auth::user()->id)->where('expense_id', $expenseId)->select('email')->get();
        return response()->json($getExpenseMember);

}


    /**
     * Get the amounts paid by the user per expense.
     *
     * @group RefundMe
     *
     * @urlParam expenseId int required The ID of the expense. Example: 1
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "amount": 50.00
     *     },
     *     {
     *         "id": 2,
     *         "amount": 75.00
     *     }
     * ]
     * @response 404 {
     *     "message": "Expense not found"
     * }
     * @get /getAmountsPaidPerExpense/{expenseId}
     */

    public function getUserAmountsPaidPerExpense(Request $request, $expenseId)
    {
        $UserAmountsPaid = userExpense::where('principal_id', Auth::user()->id)->where('expense_id', $expenseId)->get();
        return response()->json($UserAmountsPaid);
    }


    /**
     * Reinitiate a transaction for an expense.
     *
     * @group RefundMe
     *
     * @urlParam expenseId int required The ID of the expense. Example: 1
     * @urlParam id int required The ID of the user expense record. Example: 1
     * @bodyParam split_method_id int The ID of the split method used. Example: 1
     * @bodyParam bankName string The name of the bank. Example: "Example Bank"
     * @bodyParam account_name string The account holder's name. Example: "John Doe"
     * @bodyParam bankCode string The bank code. Example: "XYZ123"
     * @bodyParam account_number string The bank account number. Example: "1234567890"
     *
     * @response 200 {
     *     "payLink": "https://paymentgateway.com/link/to/transaction"
     * }
     * @response 403 {
     *     "message": "You are not authorized to perform this action."
     * }
     * @response 422 {
     *     "message": "Whoops! [Error details]"
     * }
     * @get /recreate-underpaid-transactions/{expenseId}/{id}
     */

public function reinitiateTransaction(Request $request, $expenseId, $id)
  {

    $expense = Expense::findOrFail($expenseId);
    $existingUserExpense = userExpense::findOrFail($id);
//    $getUniqueId = userExpense::findorFail($id);
    // Find the existing UserExpense record with the desired uidd
  //  $existingUserExpense = UserExpense::where('principal_id', Auth::user()->id)
//	->where('id', $id)
       // ->first();
if ($expense->user_id !== Auth::user()->id) {
        return response([
            'message' => 'You are not authorized to perform this action.',
        ], 403);
    }



 if ($existingUserExpense->principal_id !== Auth::user()->id) {
        return response([
            'message' => 'You are not authorized to perform this action.',
        ], 403);
    }

    if (!$existingUserExpense) {
        // If the UserExpense with the desired uidd is not found, return an error response
        return response([
            'message' => 'Invalid id. Please provide a valid id for the existing transaction.',
        ], 422);
    }
//	return $existingUserExpense;

    // Fetch the invited user's first and last name based on their email address
    $invitedUser = Invited::where('email', $existingUserExpense->email)->first();
    $firstName = $invitedUser->first_name ?? 'Unknown';
    $lastName = $invitedUser->last_name ?? '';

    // Calculate the payable amount for the new transaction
    $payable = $existingUserExpense->payable - $existingUserExpense->residualAmount;

        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);

        $productId = env('PayThru_expense_productid');
        $prodUrl = env('PayThru_Base_Live_Url');


        $data = [
            'amount' => $payable,
            'productId' => $productId,
            'transactionReference' => time() . $expenseId,
            'paymentDescription' => $expense->description,
            'paymentType' => 1,
            'sign' => hash('sha512', $payable . env('PayThru_App_Secret')),
            'displaySummary' => true,
        ];

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
//$user = Invited::where('auth_id', Auth::user()->id)->where('email', $em)->first();
            $slip = ['paylink' => $paylink, 'amount' => $data['amount'], 'receipient' => $existingUserExpense->email];
            $authmail = Auth::user();
//$userss = Invited::where('auth_id', Auth::user()->id)->where('email', $slip['receipient'])->first();
$uxer = $invitedUser->first_name;

Mail::to($slip['receipient'], $authmail['name'], $uxer)->send(new SendUserInviteMail($slip, $authmail, $uxer));

            if ($paylink) {
                $getLastString = explode('/', $paylink);
                $now = end($getLastString);

		// Create a new UserExpense record for the new transaction with the first and last name of the invited user
    $info = UserExpense::create([
        'principal_id' => Auth::user()->id,
        'expense_id' => $expenseId,
        'name' => $firstName . ' ' . $lastName,
        'uique_code' => $expense->uique_code,
        'email' => $existingUserExpense->email,
        'description' => $expense->description,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'split_method_id' => $request['split_method_id'],
        'payable' => $payable,
        'actualAmount' => $expense->actual_amount,
        'bankName' => $request['bankName'],
        'account_name' => $request['account_name'],
        'bankCode' => $request['bankCode'],
        'account_number' => $request['account_number'],
        'uidd' => Str::random(10),
	'paymentReference' => $now
    ]);
            }
            return response()->json($transaction);
        }
    }



 public function checkResidual($expenseId)
    {
      $checkAmountPayable = UserExpense::where('principal_id', Auth::user()->id)
      ->where('expense_id', $expenseId)
      ->sum('payable');
      $totalResidual = UserExpense::where('principal_id', Auth::user()->id)
      ->where('expense_id', $expenseId)
      ->sum('residualAmount');

 // return $totalResidual;
  if($totalResidual != 0)
    {
      $remainingPayablee = $checkAmountPayable - $totalResidual;
      return $remainingPayablee;
    }else{
      return response()->json(['message' => 'No credit yet.'], 422);
    }
    }

}
