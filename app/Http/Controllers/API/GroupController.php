<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\GroupRequest;

class GroupController extends Controller
{
    //

    public function createGroup(GroupRequest $request)
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
        
        
        public function inviteUsersToGroup(Request $request, $groupId)
        {               
        $expense = expense::find($groupId);
        $input['user_id'] = $request->user_id;
        $input['email'] = $request->input('email');
        $input['group_id'] = $expense->id;
        $input['reference_id'] = Auth::user()->id;
        $input['split_method_id'] = $request->splitting_method_id;
        $input['amount_payable'] = $expense->amount;
        $email = $request->email;
        if(User::where('email', $email)->doesntExist())
        {
        //send email
        $auth = auth()->user();
        $group = expense::where('id', $expense->id)->first();
        $purpose = $group->name;
       // if(($input['user_id'] || $input['email']) > 0 )
       // {
        //    foreach ($request->$input['user_id'] $$ $request->$input['email'] as $key)
         //   {
          //      $input['group_id'] = $expense->id;
          //      $input['reference_id'] = Auth::user()->id;
           //     $input['amount_payable'] = $expense->amount;
          //  }
       // }
        $data =['name'=>$purpose];
        //return $data;
        Mail::send('Email.userGroup', $data, function ($message) use ($email) {
        $message->to($email);
        $message->subject('AzatMe: Send Group invite');
        });
        
        }
        
        //Todo Gateway endpoints here...
        
        $info = userGroup::create($input);
        
        return response()->json($info);
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

        public function getOneGroupPerUser($id)
        {
// $getAuthUser = Auth::user();
  $get = Expense::find($id);
  $getUserExpenses = UserGroup::where('expense_id', $get)->first();
  return response()->json($getUserExpenses);

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
