<?php

namespace App\Http\Controllers\API;

use App\Feedback;
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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpenseExport;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * @group Admin
     *
     * API endpoints for admin functionalities.
     */

    /**
     * Register a new admin.
     *
     * @bodyParam name string required The admin's full name. Example: Admin User
     * @bodyParam email string required The admin's email address. Example: admin@example.com
     * @bodyParam password string required The admin's password. Example: adminpassword
     * @bodyParam password_confirmation string required Confirmation of the admin's password. Example: adminpassword
     *
     * @response 201 {
     *   "status": "success",
     *   "data": {
     *     "admin": {
     *       "id": 1,
     *       "email": "admin@example.com",
     *       "name": "Admin User"
     *     }
     *   },
     *   "message": "Admin registration successful."
     * }
     *
     * @response 400 {
     *   "status": "error",
     *   "message": "Validation errors.",
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The passwords do not match."]
     *   }
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /admin/register
     */

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
     * Get all expenses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 500,
     *       "description": "Office Supplies",
     *       "date": "2024-08-19",
     *       "email": "user@example.com"
     *     }
     *   ],
     *   "message": "Expenses retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No expenses found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /allExpenses
     */
    public function getAllExpenses(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $getAllExpenses = UserExpense::paginate($perPage);
            return response()->json($getAllExpenses);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unsuccessful'], 500);
        }
    }

    /**
     * Get all Kontributes.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 1000,
     *       "description": "Monthly Contribution",
     *       "date": "2024-08-19",
     *       "email": "user@example.com"
     *     }
     *   ],
     *   "message": "Kontributes retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No Kontributes found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /allKontributes
     */
    public function getAllKontribute(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $getAllKontribute = UserGroup::paginate($perPage);
            return response()->json($getAllKontribute);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unsuccessful'], 500);
        }
    }


    /**
     * Get all businesses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Business Name",
     *       "email": "user@example.com",
     *       "created_at": "2024-08-19"
     *     }
     *   ],
     *   "message": "Businesses retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No businesses found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /getAllBusiness
     */
    public function getAllBusiness(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $getAllBusiness = BusinessTransaction::paginate($perPage);
            return response()->json($getAllBusiness);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unsuccessful'], 500);
        }
    }

    /**
     * @group Ajo Management
     *
     * Retrieve all Ajo Invitations.
     *
     * This endpoint retrieves a paginated list of all Ajo invitations. The number of results per page can be controlled using the `per_page` query parameter.
     *
     * @queryParam per_page int optional The number of results to return per page. Default is 10. Example: 15
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "inviter_id": 2,
     *             "ajo_name": "Ajo Group 1",
     *             "description": "Monthly savings group.",
     *             "status": "active",
     *             "created_at": "2024-01-01T00:00:00.000000Z",
     *             "updated_at": "2024-01-01T00:00:00.000000Z"
     *         },
     *         {
     *             "id": 2,
     *             "inviter_id": 3,
     *             "ajo_name": "Ajo Group 2",
     *             "description": "Weekly savings group.",
     *             "status": "inactive",
     *             "created_at": "2024-01-02T00:00:00.000000Z",
     *             "updated_at": "2024-01-02T00:00:00.000000Z"
     *         }
     *     ],
     *     "first_page_url": "http://localhost/api/ajo?page=1",
     *     "from": 1,
     *     "last_page": 10,
     *     "last_page_url": "http://localhost/api/ajo?page=10",
     *     "next_page_url": "http://localhost/api/ajo?page=2",
     *     "path": "http://localhost/api/ajo",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 10,
     *     "total": 100
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     * @get /ajo
     */

    public function getAllAjo(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $getAllAjo = Invitation::paginate($perPage);
            return response()->json($getAllAjo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unsuccessful'], 500);
        }
    }

    /**
     * Get all expenses by user email.
     *
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 500,
     *       "description": "Office Supplies",
     *       "date": "2024-08-19",
     *       "email": "user@example.com"
     *     }
     *   ],
     *   "message": "Expenses retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No expenses found for the specified email."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /getAllExpensesByUserEmail/{email}
     */

    public function getAllExpensesByUserEmail(Request $request, $email)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            $getAllExpenses = UserExpense::where('user_id', $user->id)->paginate($perPage);
            return response()->json($getAllExpenses);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unsuccessful'], 500);
        }
    }

    /**
     * Get all Kontributes by user email.
     *
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 1000,
     *       "description": "Monthly Contribution",
     *       "date": "2024-08-19",
     *       "email": "user@example.com"
     *     }
     *   ],
     *   "message": "Kontributes retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No Kontributes found for the specified email."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /getAllKontributeByUserEmail/{email}
     */
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


    /**
     * Get all businesses by user email.
     *
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Business Name",
     *       "email": "user@example.com",
     *       "created_at": "2024-08-19"
     *     }
     *   ],
     *   "message": "Businesses retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No businesses found for the specified email."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /getAllBusinessByUserEmail/{email}
     */
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

    /**
     * Get all Ajo by user email.
     *
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Ajo Name",
     *       "amount": 500,
     *       "created_at": "2024-08-19",
     *       "email": "user@example.com"
     *     }
     *   ],
     *   "message": "Ajo retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No Ajo found for the specified email."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /getAllAjoByUserEmail/{email}
     */
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

    /**
     * Count all expenses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "count": 20
     *   },
     *   "message": "Expense count retrieved successfully."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /countAllExpenses
     */
    public function countAllExpenses()
  {
      try {
          $countExpenses = UserExpense::count();
          return response()->json(['count' => $countExpenses]);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }

    /**
     * @group Kontribute Counts
     *
     * Count the total number of user contributions (Kontributes).
     *
     * This endpoint returns the total count of all user contributions (Kontributes) in the system.
     *
     * @response 200 {
     *     "count": 50
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     * @get /countAllKontributes
     */

  public function countAllKontributes()
  {
      try {
          $countKontributes = UserGroup::count();
          return response()->json(['count' => $countKontributes]);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }

    /**
     * Count all Ajo records.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "count": 10
     *   },
     *   "message": "Ajo count retrieved successfully."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /countAllAjo
     */

    public function countAllAjo()
  {
      try {
          $countAjos = Invitation::count();
          return response()->json(['count' => $countAjos]);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }

    /**
     * Count all businesses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "count": 5
     *   },
     *   "message": "Business count retrieved successfully."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /countAllBusiness
     */


    public function countAllBusiness()
  {
      try {
          $countBusiness = BusinessTransaction::count();
          return response()->json(['count' => $countBusiness]);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }
    /**
     * Get all active expenses for a specific refundme ID.
     *
     * @urlParam refundmeId integer required The ID of the refundme expense. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 500,
     *       "description": "Office Supplies",
     *       "date": "2024-08-19"
     *     }
     *   ],
     *   "message": "Active expenses retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No active expenses found for the specified ID."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-all-active-expense/{refundmeId}
     */

  public function getActiveExpense($expenseId)
  {
      try {
          $expense = UserExpense::findOrFail($expenseId);
          if ($expense->status !== null) {
              return response()->json($expense);
          } else {
              return response()->json(['error' => 'Expense is not active'], 404);
          }
      } catch (\Exception $e) {
          return response()->json(['error' => 'Unsuccessful'], 500);
      }
  }
    /**
     * @group Expenses
     *
     * Count the number of active expenses for a given ID.
     *
     * This endpoint returns the count of active expenses for a specific `refundmeId` if the authenticated user is an admin.
     * If the user is not an admin, an authorization error is returned.
     *
     * @urlParam refundmeId int required The ID of the expense to count. Example: 1
     *
     * @response 200 {
     *     "count": 5
     * }
     *
     * @response 403 {
     *     "error": "Auth user is not an admin"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */

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

    /**
     * Get all users added to a specific Kontribute.
     *
     * @urlParam kontributeId integer required The ID of the Kontribute. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "email": "user1@example.com",
     *       "name": "User One"
     *     },
     *     {
     *       "id": 2,
     *       "email": "user2@example.com",
     *       "name": "User Two"
     *     }
     *   ],
     *   "message": "Users added to the Kontribute retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No users found for the specified Kontribute."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-all-AddedUser-Kontribute/{kontributeId}
     */
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

    /**
     * @group Kontribute
     *
     * Count the number of users added to a specific Kontribute group by ID.
     *
     * This endpoint returns the count of users associated with a given `kontributeId` if the authenticated user is an admin.
     * If the user is not an admin, an authorization error is returned.
     *
     * @urlParam kontributeId int required The ID of the Kontribute group. Example: 1
     *
     * @response 200 {
     *     "count": 10
     * }
     *
     * @response 403 {
     *     "error": "Auth user is not an admin"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */


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

    /**
     * @group Admin
     *
     * API endpoints for admin functionalities.
     */

    /**
     * Get all users added to a specific expense.
     *
     * @urlParam refundmeId integer required The ID of the refundme expense. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "email": "user1@example.com",
     *       "name": "User One"
     *     },
     *     {
     *       "id": 2,
     *       "email": "user2@example.com",
     *       "name": "User Two"
     *     }
     *   ],
     *   "message": "Users added to the expense retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No users found for the specified expense."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-all-AddedUser-Expenses/{refundmeId}
     */
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

    /**
     * @group Expenses
     *
     * Count the number of users associated with a specific expense ID.
     *
     * This endpoint returns the count of users associated with the given `refundmeId` if the authenticated user is an admin.
     * If the user is not an admin, an authorization error is returned.
     *
     * @urlParam refundmeId int required The ID of the expense. Example: 1
     *
     * @response 200 {
     *     "count": 5
     * }
     *
     * @response 403 {
     *     "error": "Auth user is not an admin"
     * }
     *
     * @response 404 {
     *     "error": "Expense not found"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */

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

    /**
     * Get all active Kontributes for a specific ID.
     *
     * @urlParam kontributeId integer required The ID of the Kontribute. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "amount": 1000,
     *       "description": "Monthly Contribution",
     *       "date": "2024-08-19"
     *     }
     *   ],
     *   "message": "Active Kontributes retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No active Kontributes found for the specified ID."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-all-active-kontribute/{kontributeId}
     */

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

    /**
     * Count all active Kontributes.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "count": 8
     *   },
     *   "message": "Count of active Kontributes retrieved successfully."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /count-all-active-users-kontributes
     */
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

    /**
     * @group Export
     *
     * Export user expenses to an Excel file.
     *
     * This endpoint exports user expenses to an Excel file if the authenticated user is an admin.
     * The file is named with a timestamp and downloaded in `.xlsx` format.
     * If the user is not an admin, an authorization error is returned.
     *
     * @queryParam per_page int optional Number of items per page. Default is 10. Example: 20
     *
     * @response 200 {
     *     "message": "Excel file is being downloaded"
     * }
     *
     * @response 403 {
     *     "error": "Auth user is not an admin"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */



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

    /**
     * @group Export
     *
     * Export user expenses to a CSV file.
     *
     * This endpoint exports user expenses to a CSV file if the authenticated user is an admin.
     * The file is named with a timestamp and downloaded in `.csv` format.
     * If the user is not an admin, an authorization error is returned.
     *
     * @queryParam per_page int optional Number of items per page. Default is 10. Example: 20
     *
     * @response 200 {
     *     "message": "CSV file is being downloaded"
     * }
     *
     * @response 403 {
     *     "error": "Auth user is not an admin"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */


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

    /**
     * Update feedback or issue.
     *
     * @urlParam complain_reference_code string required The reference code of the complaint. Example: CR123456
     * @bodyParam status string required The status of the complaint (e.g., resolved, pending). Example: resolved
     * @bodyParam notes string optional Additional notes or comments regarding the complaint. Example: Issue resolved after updating payment details.
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Feedback updated successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Complaint not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @put /admin/update-feedback/{complain_reference_code}
     */

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

    /**
     * Get all users.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "email": "user@example.com",
     *       "name": "User One",
     *       "created_at": "2024-08-19"
     *     }
     *   ],
     *   "message": "Users retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "No users found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /all-users
     */

    public function getAllUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $users = User::paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get user by email.
     *
     * @urlParam email string required The email of the user. Example: user@example.com
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "id": 1,
     *     "email": "user@example.com",
     *     "name": "User One",
     *     "created_at": "2024-08-19"
     *   },
     *   "message": "User retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "User not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /users/{email}
     */

    public function getUserById($email): \Illuminate\Http\JsonResponse
    {
        $user = User::find($email);

        if ($user) {
            return response()->json($user);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**
     * Close a complaint by marking it as completed.
     *
     * @urlParam complainId integer required The ID of the complaint to be marked as completed. Example: 123
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Complaint marked as completed successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Complaint not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /close-complain/{complainId}
     */

    public function markAsCompleted(Request $request, $complainId): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:unresolved,resolved',
        ]);
        // Find the feedback record
        $feedback = Feedback::findOrFail($complainId);
        // Update the status
        $feedback->status = $request->status;
        $feedback->save();
        return response()->json(['message' => 'Feedback status updated to completed', 'data' => $feedback], 200);
    }

}










