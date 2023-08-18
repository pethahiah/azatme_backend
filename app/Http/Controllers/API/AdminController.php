<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Admin;
use App\Expense;
use App\userExpense;
use App\userGroup;



class AdminController extends Controller
{
    /**
     * Register a new admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = new Admin([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);
        $admin->save();

        return response()->json(['message' => 'Admin created successfully']);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */



  public function getAllExpenses()
  {
   $paginate = 50;
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = Expense::whereNotNull('subCategory_id')->paginate($paginate);
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }


public function countAllExpenses()
  {
   $paginate = 50;
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = Expense::whereNotNull('subCategory_id')->count();
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }

public function getUserAddedToExpense($refundmeId)
{
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
        $getUserExpense = UserExpense::where('id', $refundmeId)->get();
        return response()->json($getUserExpense);
    } else {
        return response()->json('Auth user is not an admin');
    }
}

public function countUserAddedToExpense($refundmeId)
{
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
        $getUserExpense = UserExpense::where('id', $refundmeId)->count();
        return response()->json($getUserExpense);
    } else {
        return response()->json('Auth user is not an admin');
    }
}

public function getActiveExpense($refundmeId)
{
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
        $getAllExpenses = UserExpense::where('id', $refundmeId)->whereNotNull('status')->paginate(50);
        return response()->json($getAllExpenses);
    } else {
        return response()->json('Auth user is not an admin');
    }
}

public function countActiveExpenses($refundmeId)
{
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
        $getAllExpenses = UserExpense::where('id', $refundmeId)->whereNotNull('status')->count();
        return response()->json($getAllExpenses);
    } else {
        return response()->json('Auth user is not an admin');
    }
}





 public function getAllKontributes()
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = Expense::whereNull('subCategory_id')->paginate(50);
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }

public function countAllKontributes()
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = Expense::whereNull('subCategory_id')->count();
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }


    public function getUserAddedToKontribute($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
    $getUserExpense = userGroup::where('id', $kontributeId)->get(paginate);
    return response()->json($getUserExpense);
    } else {
    return response()->json('Auth user is not an admin');
    }
  }

 public function countUserAddedToKontribute($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
    $getUserExpense = userGroup::where('id', $kontributeId)->count();
    return response()->json($getUserExpense);
    } else {
    return response()->json('Auth user is not an admin');
    }
  }


  public function getActiveKontribte($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = UserGroup::where('id', $KontributeId)->whereNotNull('status')->paginate(50);
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }



 public function countActiveKontribtes($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = UserGroup::where('id', $KontributeId)->whereNotNull('status')->count();
      return response()->json($getAllExpenses);
    } else {
      return response()->json('Auth user is not an admin');
    }
  }




  public function exportExpenseToExcel(Request $request)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
    $fileName = 'azatme_report' . '_' . Carbon::now() . '.' . 'xlsx';
    $userExpense = userExpense::getuserExpense($request);
    Log::info($userExpense);
    ob_end_clean();
    return Excel::download(new ExpenseExport($userExpense), $fileName);
} else {
    return response()->json('Auth user is not an admin');
  }
}


  public function exportExpenseToCsv(Request $request)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
    $fileName = 'azatme_report' . '_' . Carbon::now() . '.' . 'csv';
    $userExpense = userExpense::getuserExpense($request);
    Log::info($userExpense);
    ob_end_clean();
    return Excel::download(new ExpenseExport($userExpense), $fileName);
} else {
    return response()->json('Auth user is not an admin');
  }
}



}










