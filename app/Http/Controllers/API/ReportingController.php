<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\ExpenseCategory;
use Auth;
use App\Expense;
use App\userExpense;
use App\userGroup;
use App\ExpenseSubCategory;

class ReportingController extends Controller
{
    //

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

public function countExpensesPerUser()
{
  $getAuthUser = Auth::user();
  $getUserExpenses = UserExpense::where('principal_id', $getAuthUser->id)->count();
  return response()->json($getUserExpenses);
}
public function getUserExpenseWithDate(Request $request)
    {
       $start_date = Carbon::parse($request->from)
                             ->toDateTimeString();

       $end_date = Carbon::parse($request->to)
                             ->toDateTimeString();

        $getAuthUser = Auth::user();
       return UserExpense::whereBetween('created_at',[$start_date,$end_date])->where('principal_id', $getAuthUser->id)->get();

    }
    
public function getUserGroupWithDate(Request $request)
    {
       $start_date = \Carbon\Carbon::parse($request->from)
                             ->format('Y-m-d'); 

       $end_date = \Carbon\Carbon::parse($request->to)
                             ->format('Y-m-d');

        $getAuthUser = Auth::user();
       return UserGroup::whereBetween('created_at',[$start_date,$end_date])->where('reference_id', $getAuthUser->id)->get();

    }

    public function getUserExpenseWithCategory(Request $request, $categoryId)
    {
     
      $start_date = Carbon::parse($request->from);
      $end_date = Carbon::parse($request->to);
            
        $getCat = ExpenseCategory::find($categoryId);

        $getCatWithId = $getCat->id;
        $getAuthUser = Auth::user();
       return Expense::whereBetween('created_at',[$start_date, $end_date])->where('user_id', $getAuthUser->id)->where('category_id', $getCatWithId)->get();

}

    public function getUserExpenseWithSubCategory(Request $request, $sub_categoryId)
    {
     
      $start_date = Carbon::parse($request->from);
      $end_date = Carbon::parse($request->to);
            
        $getCat = ExpenseCategory::find($sub_categoryId);

        $getCatWithId = $getCat->id;
       // return $getCatWithId;
        $getAuthUser = Auth::user();
       return Expense::whereBetween('created_at',[$start_date, $end_date])->where('user_id', $getAuthUser->id)->where('category_id', $getCatWithId)->get();

}
}
