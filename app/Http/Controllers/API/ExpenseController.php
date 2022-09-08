<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Expense;
use App\User;
use App\userExpense;
use App\UserGroup;
use Carbon\Carbon;
use Auth;
use Mail;
use App\Http\Requests\ExpenseRequest;
use Illuminate\Support\Str;
use App\Jobs\ProcessBulkExcel;
use Illuminate\Http\Response;
use App\Helper\Reply;
use illuminate\Support\Facades\Http;

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
            'amount' => $request->amount,
            'user_id' => Auth::user()->id
        ]);

        return response()->json($expense);

    }

    public function inviteUserToExpense(Request $request, $expenseId)
    {
      $expense = expense::findOrFail($expenseId);
      $Id['expense_id'] = $expense->id;
      $Id['principal_id'] = Auth::user()->id;
      $Id['name'] = $expense->name;
      $Id['description'] = $expense->description;
      $Id['split_method_id'] = $request->splitting_method_id;
      $Id['user_id'] = $request->user_id;
      $Id['payable'] = $expense->amount;
      $input['email'] = $request->input('email');
        $emails = $request->email;
        if($emails)
        {
        $emailArray = (explode(';', $emails));
        foreach ($emailArray as $key => $user) {
            //Each user is process in iteration to have single email
            if ((User::where('email', $user)->doesntExist()) )
        {
            //send email
            $auth = auth()->user();
            Mail::send('Email.userInvite', ['user' => $auth], function ($message) use ($user) {
                $message->to($user);
                $message->subject('AzatMe: Send expense invite');
            });
          }
        }
        }
      //Gateway integration
      $ProductId = env('PayThru_ProductId');
      $BaseTestUrl = env('PayThru_Base_Test_Url');
      $BaseLiveUrl = env('PayThru_Base_Live_Url');

      $resp = Http::acceptJson()->post($BaseTestUrl.'transaction/create', [
      'amount' => $expense->amount,
      'productId' => $ProductId,
      'transactionReference' => $expense->id,
      'paymentDescription' => $expense->description,
      'sign' => "",
      'paymentType' => $request->splitting_method_id,
      'displaySummary' => true
      ]);

      if($resp->ok())
      {
        $transaction = json_decode($resp->body(), true);
        $info = userExpense::create($Id);
      }
      return response()->json($info, $transaction);
        
    }


    //Calling gateway for transaction response updates
    public function webhookResponse()
    {
        $response = file_get_contents('https://azatme.eduland.ng/api/updateStatus');
        $data = json_decode($response->getBody());
        if($data->status == 1) {
            $statusUpdate = userExpense::where('expense_id', $data->transactionReference)->update([
            'transactionDate' => $data->transactionDate,
            'fiName' => $data->fiName,
            'status' => $data->status,
            'amount' => $data->amount,
            'customerName' => $data->customerName,
            'paymentReference' => $data->paymentReference,
            'payThruReference' => $data->payThruReference,
            'paymentReference' => $data->paymentReference,
            'merchantReference' => $data->merchantReference,
        ]);
        }     
    }

  private function userEmailToId($email){
      return User::select('id')->where('email',$email)->first()->value('id');  
  }

public function allExpensesPerUser()
{

  $pageNumber = 5;
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->latest()->paginate($pageNumber);
  return response()->json($getUserExpenses);

}

public function getRandomUserExpense($user_id)
{
$getUserExpense = userExpense::where('principal_id', Auth::user()->id)->where('user_id', $user_id)->get();
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
    $update = Expense::find($id);
    $update->update($request->all());
    return response()->json($update);

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
}
