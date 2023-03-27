<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Expense;
use App\User;
use App\Verifysms;
use App\userExpense;
use App\Withdrawal;
use App\UserGroup;
use App\Bank;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
use Mail;
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
use Excel;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use DB;
use App\Setting;
use Storage;


class ExpenseController extends Controller
{
    //
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
       $prodUrl = env('PayThru_Base_Live_Url');


       $dataa = [
        'ApplicationId' => $PayThru_AppId,
        'password' => $hash
      ];
      //return $data;
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Timestamp' => $timestamp,
  ])->post('https://services.paythru.ng/identity/auth/login', $dataa);
    //return $response;
    if($response->Successful())
    {
      $access = $response->object();
      $accesss = $access->data;
      $paythru = "Paythru";
  
      $token = $paythru." ".$accesss;

       //return "AppId ".  $PayThru_AppId;
    
      $emails = $request->email;
      //return $emails;
      if($emails)
      {
      $emailArray = (explode(';', $emails));
      $count = count($emailArray);
     // return response()->json($emailArray);
      
      $payers = [];
      $totalpayable = 0;
      foreach ($emailArray as $key => $em) {
          //process each user here as each iteration gives you each email
          $user = User::where('email', $em)->first();
      
        $payable = 0;

        if($request['split_method_id'] == 1)
        {
            $payable = $expense->amount;
  
        } elseif($request['split_method_id'] == 2)
        {
          if(isset($request->percentage))
          {
            $payable = $expense->amount*$request->percentage/100;
          }elseif(isset($request->percentage_per_user))
          {
              //json_decode(json_encode($data),true);
            $ppu = json_decode($request->percentage_per_user);
            //return $em;
            
            $payable = $ppu->$em*$expense->amount/100;
          }
        }elseif($request['split_method_id'] == 3)
        {
           //$payable = $expense->amount/$count;
            $payable = round(($expense->amount / $count), 2);
         
            if ($key == $count - 1) {
        $payable = $expense->amount - (round($payable, 2) * ($count - 1));
        }
            
        }elseif($request['split_method_id'] == 4)
        {
            $payable = $expense->amount/$count;
        }
        
         $paylink_expiration_time = Carbon::now()->addHours(23);

          $info = userExpense::create([
            'principal_id' => Auth::user()->id,
            'expense_id' => $expense->id,
            'name' => $expense->name,
            'uique_code' => $expense->uique_code,
            'email' => $em,
            'description' => $expense['description'],
            'split_method_id' => $request['split_method_id'],
            'payable' => $payable,
            'linkExpireDateTime'=> $paylink_expiration_time,
            'actualAmount' => $expense->actual_amount,
            'bankName' => $request['bankName'],
            'account_name' => $request['account_name'],
            'bankCode' => $request['bankCode'],
            'account_number' => $request['account_number'],
          ]);
          
         
         $payers[] =  ["payerEmail" => $em, "paymentAmount" => $info->payable];
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
        'expireDateTime'=> $paylink_expiration_time,
        'displaySummary' => true,
        'splitPayInfo' => [
            'inviteSome' => false,
            'payers' => $payers
          ]

        ];
        
        // $param = Setting::where('id', 1)->first();
        // $token = $param->token;
        
        $authmail = Auth::user()->email; 
      
      //return $token;
        $url = $prodUrl;
        $urls = $url.'/transaction/create';

       $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
      ])->post($urls, $data );
      if($response->failed())
      {
        return false;
      }else{
        $transaction = json_decode($response->body(), true);
        //return $transaction;
        $splitResult = $transaction['splitPayResult']['result'];
        foreach($splitResult as $key => $slip)
        {
          Mail::to($slip['receipient'], $authmail)->send(new SendUserInviteMail($slip));
          
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
      return response()->json($transaction);
      }
    }
    }
    
    
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
    
    
         //Calling PayThru gateway for transaction response updates
     public function webhookExpenseResponse(Request $request)
   {
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
        //Log::info("webhook-data" . json_encode($da));
        if($data->transactionDetails->status == 'Successful'){
         // return "good";
        $userExpense = userExpense::where('paymentReference', $data->transactionDetails->paymentReference)->update([
            'payThruReference' => $data->transactionDetails->payThruReference,
            'fiName' => $data->transactionDetails->fiName,
            'status' => $data->transactionDetails->status,
            'amount' => $data->transactionDetails->amount,
            'responseCode' => $data->transactionDetails->responseCode,
            'paymentMethod' => $data->transactionDetails->paymentMethod,
            'commission' => $data->transactionDetails->commission,
            'residualAmount' => $data->transactionDetails->residualAmount,
            'resultCode' => $data->transactionDetails->resultCode,
            'responseDescription' => $data->transactionDetails->responseDescription,
           
        ]);
          Log::info("done");
          
          http_response_code(200);

        }else
       return response([
                'message' => 'data does not exists'
            ], 401);
    }

 
  private function userEmailToId($email){
      return User::select('id')->where('email',$email)->first()->value('id');  
  }
  

public function getUserExpense()
{
   
$pageNumber = 50;
$getAuthUser = Auth::user();
$getUserExpense = Expense::where('user_id', $getAuthUser->id)->whereNotNull('subcategory_id')->paginate($pageNumber);
$getUserExpenseAddedTransactions = userExpense::where('email', $getAuthUser->email)->paginate($pageNumber);
return response()->json([
                'getAuthUserExpensesCreated' => $getUserExpense,
                'getExpensesInvitedTo' => $getUserExpenseAddedTransactions,
            ]);
}

public function allExpensesPerUser()
{

  $pageNumber = 50;
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->latest()->paginate($pageNumber);
  return response()->json($getUserExpenses);

}



public function getRandomUserExpense($email)
{
$getUserExpense = userExpense::where('principal_id', Auth::user()->id)->where('email', $email)->get();
return response()->json($getUserExpense);

}

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

public function countExpensesPerUser()
{
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->count();
  return response()->json($getUserExpenses);
}

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
      Log::info($userExpense);
      ob_end_clean();
      return Excel::download(new ExpenseExport($userExpense), $fileName);
    }

    public function exportExpenseToCsv(Request $request)
    {
      $fileName = 'azatme_report'.'_'.Carbon::now() . '.' . 'csv';
      $userExpense = userExpense::getuserExpense($request);
      Log::info($userExpense);
      ob_end_clean();
      return Excel::download(new ExpenseExport($userExpense), $fileName);
    }
    
    public function AzatIndividualCollection(Request $request, $transactionId)
    {
      $current_timestamp= now();
     // return $current_timestamp;
      $timestamp = strtotime($current_timestamp);
      $secret = env('PayThru_App_Secret');
      $productId = env('PayThru_expense_productid');
      //return $productId;
      $hash = hash('sha512', $timestamp . $secret);
      //return $hash;
      $AppId = env('PayThru_ApplicationId');
      $prodUrl = env('PayThru_Base_Live_Url');

      $expenseAmount = userExpense::where('principal_id', Auth::user()->id)->where('expense_id', $transactionId)->whereNotNull('actualAmount')->first();
      $amount = $expenseAmount->actualAmount;
   // return $amount;
   // return Auth::user()->id;
    
      $withdrawal = new Withdrawal([
        
        'account_number' => $request->account_number,
        'description' => $request->description,
        'expense_id' => $expenseAmount->id,
        'beneficiary_id' => Auth::user()->id,
        'amount' => $request->amount,
        'bank' => $request->bank
        ]);
        
        
        $getUserExpenseTransactions = userExpense::where('principal_id', Auth::user()->id)->sum('residualAmount');
        
        if(($request->amount) > $amount)
        {
            if(($request->amount) > $getUserExpenseTransactions)
            {
                return response([
            'message' => 'You dont not have sufficient amount in your RefundMe'
        ], 403);
            }else{
          return response([
            'message' => 'Please enter correct refund amount'
        ], 403);
        }
        }
              $withdrawal->save();
              
              $acct = $request->account_number;
    
   $getBankReferenceId = Bank::where('user_id', Auth::user()->id)->where('account_number', $acct)->first();
   $getAccountName = $getBankReferenceId->account_name;
   //return $getAccountName;
   
   $beneficiaryReferenceId = $getBankReferenceId->referenceId;
    $dataa = [
      'ApplicationId' => $PayThru_AppId,
      'password' => $hash
    ];
    //return $data;
  $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Timestamp' => $timestamp,
])->post('https://services.paythru.ng/identity/auth/login', $dataa);
  //return $response;
  if($response->Successful())
  {
    $access = $response->object();
    $accesss = $access->data;
    $paythru = "Paythru";

    $token = $paythru." ".$accesss;
   //return $beneficiaryReferenceId;
      $data = [
            'productId' => $productId,
            'amount' => $amount,
            'beneficiary' => [
            'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];
   //return $data;
      //return $token;
        $url = $prodUrl;
        $urls = $url.'/transaction/settlement';
    
         $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
      ])->post($urls, $data );
     //return $response->transactionReference;
      if($response->failed())
      {
        return false;
      }else{
          
        //$collection = json_decode($response->body(), true);
        //dd($collection);
        $collection = $response->object();
        $saveTransactionReference = Withdrawal::where(['expense_id' => $expenseAmount->id, 'beneficiary_id' => Auth::user()->id])->update([
            'transactionReference' => $collection->transactionReference,
        ]);
       // $collection->transactionReference;
      return response()->json($collection);
    }
  }
}


public function refundmeSettlementWebhookResponse(Request $request)
   {
       
        $productId = env('PayThru_expense_productid');
       
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
        if($data->notificationType == 2){
         // return "good";
        $updatePaybackWithdrawal = Withdrawal::where(['transactionReferences'=> $data->transactionDetails->transactionReferences, $productId => $data->transactionDetails->productId])->update([
            'paymentAmount' => $data->transactionDetails->paymentAmount,
            'recordDateTime' => $data->transactionDetails->recordDateTime,
        ]);
          Log::info("payback settlement done");
          http_response_code(200);

        }else
       return response([
                'message' => 'data does not exists'
            ], 401);
    }


public function accountVerification(Request $request)
{

$user = Auth::user()->id;
//return $user;
$prodUrl = env('PayThru_Base_Live_Url');
$account = $request->account_number;
$bankCode = $request->bankCode;

$param = Setting::where('id', 1)->first();
$token = $param->token;
$getLastName = User::where('id', $user)->first();
$last = $getLastName->last_name;
$first = $getLastName->first_name;
$middle_name = $getLastName->middle_name;
$fullName = $last.' '.$first.' '.$middle_name;
$fullNames =$first.' '.$middle_name.' '.$last;
    
    $dataa = [
      'ApplicationId' => $PayThru_AppId,
      'password' => $hash
    ];
    //return $data;
  $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Timestamp' => $timestamp,
])->post('https://services.paythru.ng/identity/auth/login', $dataa);
  //return $response;
  if($response->Successful())
  {
    $access = $response->object();
    $accesss = $access->data;
    $paythru = "Paythru";

    $token = $paythru." ".$accesss;

//return $fullName;
   $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
  ])->get("https://services.paythru.ng/cardfree/bankinfo/nameInfo/$account/$bankCode");
  if($response->Successful())
  //return $response;
    {   
        
  $details = $response->object();
  
  $getData = $details->data;
 
 return response()->json($details);
    
 //}
    }
  }
    return null;
    
}
 
    public function getAllMemebersOfAnExpense($expenseId)
{
        $getExpenseMember = userExpense::where('principal_id', Auth::user()->id)->where('expense_id', $expenseId)->select('email')->get();
        return response()->json($getExpenseMember);

}

    public function getUserAmountsPaidPerExpense(Request $request, $expenseId)
    {
        $UserAmountsPaid = userExpense::where('principal_id', Auth::user()->id)->where('expense_id', $expenseId)->get();
        return response()->json($UserAmountsPaid);
    }
       
}
