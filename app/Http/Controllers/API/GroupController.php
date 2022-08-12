<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\GroupRequest;
use App\Expense;
use Illuminate\Support\Str;
use Auth;
use App\User;
use Mail;
use App\userGroup;

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
        $input['group_id'] = $expense->id;
        $input['reference_id'] = Auth::user()->id;
        $input['split_method_id'] = $request->splitting_method_id;
        $input['amount_payable'] = $expense->amount;
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
            Mail::send('Email.userGroup', ['user' => $auth], function ($message) use ($user) {
                $message->to($user);
                $message->subject('AzatMe: Send group invite');
            });

          }
        } 
        
          $input['user_id'] = $request->user_id;
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


        public function getRandomUserGroup($user_id)
{

        $getUserGroup = userGroup::where('reference_id', Auth::user()->id)->where('user_id', $user_id)->first();
        return response()->json($getUserGroup);

}


        public function getOneGroupPerUser($id)
        {
// $getAuthUser = Auth::user();
        $get = userGroup::find($id);
        $getUserGroup = UserGroup::where('expense_id', $get)->first();
         return response()->json($getUserGroup);

        }

        public function deleteInvitedGroupUser($user_id) 
{

        $deleteInvitedExpenseUser = userGroup::findOrFail($user_id);
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

}
