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

class ExpenseController extends Controller
{
    //
public function createExpense(ExpenseRequest $request){
     
            $expense = Expense::create([
                    'name'=> $request->name,
                    'description' => $request->description,
                    'uique_code'=> Str::random(10),
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
        $input['expense_id'] = $expense->id;
        $input['description'] = $request->description;
        $input['principal_id'] = Auth::user()->id;
        $input['payable'] = $expense->amount;
        $input['split_method_id'] = $request->splitting_method_id;
        $input['email'] = $request->input('email');
        $emails = $request->email;
        $input['user_id'] = $request->user_id;
        $userids = $request->user_id;
        //if $request->user_id is comma separated i.e. 1,2,3 etc
        if(($userids) > 0)
        {
        $useridArray = explode(';', $userids);
        //return $useridArray;
        foreach ($useridArray as $key => $user) {
            //process each user here as each iteration gives you each user id
            //i guess you want to fetch the user, insert into expense and send notification
            $info = userExpense::create($input);
            return $info;
           
        }

      }
        if($emails)
        {
        $emailArray = (explode(';', $emails));
        //return $emailArray;
        foreach ($emailArray as $key => $user) {
            //process each user here as each iteration gives you each email
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
      }else{
        //Todo Gateway endpoints here...
        $info = userExpense::create($input);
        return response()->json($info);
      }

       

        

         
    }


public function allExpensesPerUser()
{

  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->get();
  return response()->json($getUserExpenses);

}

public function getRandomUserExpense($user_id)
{
// $getAuthUser = Auth::user();
$getUserExpense = userExpense::where('principal_id', Auth::user()->id)->where('user_id', $user_id)->first();
return response()->json($getUserExpense);

}

public function getAllExpenses()
{

  $getAdmin = Auth::user();
  $getAd = $getAdmin -> usertype;
  //return $getAd;
  
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
//$userDelete = Expense::where('user', $user)
   $deleteInvitedExpenseUser->delete(); 
else
return response()->json(null); 
}

    
    public function deleteExpense($id) 
    {
    //$user = Auth()->user();
    $deleteExpense = Expense::findOrFail($id);
    $deleteExpenses = expense::where('user_id', Auth::user()->id)->where('id', $deleteExpense);
    if($deleteExpenses)
    //$userDelete = Expense::where('user', $user)
       $deleteExpenses->delete(); 
    else
    return response()->json(null); 
}

public function BulkUploadInviteUsersToExpense(Request $request)

    {
        try {
            $file = $request->file('file_upload');
            $extension = $file->extension();
            $file_name = 'user_to_expense_' . time() . '.' . $extension;
            $file->storeAs(
                'excel bulk import', $file_name
            );
            ProcessBulkExcel::dispatchSync($file_name);
            return response()->json(Reply::success(__('messages.import_excel_successful')), Response::HTTP_OK);
        }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json(Reply::error(__('messages.import_failed'), [$e]), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

  }