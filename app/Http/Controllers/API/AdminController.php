<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Admin;
use App\User;
use App\Expense;
use App\userExpense;
use App\UserGroup;
use App\BusinessTransaction;
use App\Invitation;



class AdminController extends Controller
{

	public function __construct()
	{
    	$this->middleware('admin');
	}

    /**
     * Register a new admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function adminsRegisters(Request $request)
{


    // Validate the request for admin registration
    $this->validate($request, [
        'name' => 'required|min:3|max:50',
        'email' => 'required|email|unique:users',
        'usertype' => 'required|string|in:admin',
        'company_name' => 'string',
        'phone' => 'string|unique:users|required',
        'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
        'password_confirmation' => 'required|same:password',
    ]);

    // Create an admin user
    $user = new User([
        'name' => $request->name,
        'email' => $request->email,
        'usertype' => $request->usertype,
        'company_name' => $request->company_name,
        'phone'=> $request->phone,
        'password' => Hash::make($request->password)
    ]);

    $user->save();
    return response()->json(['message' => 'Admin user has been registered', 'data' => $user], 200);
}



public function adminRegister(Request $request)
{
    if (Auth::check() && Auth::user()->usertype === 'admin') {

        // Validate the request for admin registration
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users',
            'usertype' => 'required|string|in:admin',
            'company_name' => 'string',
            'phone' => 'string|unique:users|required',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => 'required|same:password',
        ]);

        // Create an admin user
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'company_name' => $request->company_name,
            'phone'=> $request->phone,
            'password' => Hash::make($request->password)
        ]);

        $user->save();
        return response()->json(['message' => 'Admin user has been registered', 'data' => $user], 200);

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}



















    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */




 public function getAllExpenses(Request $request)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getAllExpenses = userExpense::paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }

  public function getAllKontribute(Request $request)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getAllExpenses = UserGroup::paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }

  public function getAllBusiness(Request $request)
  {
  try {
      $perPage = $request->input('per_page', 10);
      $getAllExpenses = BusinessTransaction::paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }


  public function getAllAjo(Request $request)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getAllExpenses = Invitation::paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }


  public function getAllExpensesByUserEmail(Request $request, $email)
  {
      try {
          $perPage = $request->input('per_page', 10);
          $getUserEmail = User::where('email', $email)->first()->id;
          $getAllExpenses = UserExpense::where('principal_id', $getUserEmail)->paginate($perPage);
          return response()->json($getAllExpenses);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }




  public function getAllKontributeByUserEmail(Request $request, $email)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getUserEmail = User::where('email', $email)->first()->id;
      $getAllExpenses = UserGroup::where('reference_id', $getUserEmail)->paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }


  public function getAllBusinessByUserEmail(Request $request, $email)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getUserEmail = User::where('email', $email)->first()->id;
      $getAllExpenses = BusinessTransaction::where('owner_id', $getUserEmail)->paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }


  public function getAllAjoByUserEmail(Request $request, $email)
  {
    try {
      $perPage = $request->input('per_page', 10);
      $getUserEmail = User::where('email', $email)->first()->id;
      $getAllExpenses = Invitation::where('inviter_id', $getUserEmail)->paginate($perPage);
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }




public function countAllExpenses()
  {

    try {
      $getAllExpenses = UserExpense::count();
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }

  public function countAllKontributes()
  {
    try {
      $getAllExpenses = UserGroup::count();
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }

  public function countAllAjo()
  {
    try {
      $getAllExpenses = Invitation::count();
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
  }
  }


  public function countAllBusiness()
  {
    try {
      $getAllExpenses = BusinessTransaction::count();
      return response()->json($getAllExpenses);
  } catch (\Exception $e) {
      return response()->json(['error' => 'Unsuccessful'], 500);
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


    public function getUserAddedToKontribute($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
    $getUserExpense = userGroup::where('id', $kontributeId)->get();
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

  public function getActiveKontribte($kontributeId)
  {
    $getAdmin = Auth::user();
    $getAd = $getAdmin->usertype;
    if ($getAd === 'admin') {
      $getAllExpenses = UserGroup::where('id', $kontributeId)->whereNotNull('status')->paginate(50);
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
      $getAllExpenses = UserGroup::where('id', $kontributeId)->whereNotNull('status')->count();
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



 public function updateIssue(Request $request, $complain_reference_code)
{
    // Get the authenticated user
    $getAdmin = Auth::user();

    // Check if the user type is admin
    if ($getAdmin->usertype === 'admin') {
        $updateIssue = Feedback::where('complain_reference_code', $complain_reference_code)->first();

        if ($updateIssue) {

                // Update the status
                $updateIssue->status = $request->status;
                $updateIssue->save();
	    return response([
                    'message' => 'Status updated to successfully',
                    'data' => $updateIssue
                ], 200);
        } else {
            return response([
                'message' => 'Reference code not found'
            ], 404);
        }
    } else {
        return response()->json('Auth user is not an admin', 403);
    }
}


    public function getAllUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $users = User::paginate($perPage);

        return response()->json($users);
    }

    public function getUserById($id): \Illuminate\Http\JsonResponse
    {
        $user = User::find($id);

        if ($user) {
            return response()->json($user);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }


}










