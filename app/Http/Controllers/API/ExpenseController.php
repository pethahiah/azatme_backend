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
                    'description' => $request->decription,
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
        $input['principal_id'] = Auth::user()->id;
        $input['payable'] = $expense->amount;
        $input['split_method_id'] = $request->splitting_method_id;
        $input['email'] = $request->input('email');
        $emails = $request->email;

        
        $emailArray = (explode(';', $emails));
        //return $emailArray;
        foreach ($emailArray as $key => $user) {
            //process each user here as each iteration gives you each email
            if ((User::where('email', $user)->doesntExist()) )

           
              if (($user) > 0)
               {
                  return "Sorry, email already exisit";
               }else
        {
            //send email
            $auth = auth()->user();
            Mail::send('Email.userInvite', ['user' => $auth], function ($message) use ($user) {
                $message->to($user);
                $message->subject('AzatMe: Send expense invite');
            });

          }
        } 
        
          $input['user_id'] = $request->user_id;

          //Todo Gateway endpoints here...

        $info = userExpense::create($input);

        return response()->json($info);
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
  $getUserExpenses = UserExpense::where('user_id', $user_id)->get();
  return response()->json($getUserExpenses);

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
//$user = Auth()->user();
//return $user;
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
    if($deleteExpense)
    //$userDelete = Expense::where('user', $user)
       $deleteExpense->delete(); 
    else
    return response()->json(null); 
}

    

  }