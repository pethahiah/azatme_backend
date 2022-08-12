<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('API')->group(function () {
    Route::post('AttemptLogin', 'AuthController@AttemptLogin');
    Route::post('register', 'AuthController@register');
    Route::post('loginViaOtp', 'AuthController@loginViaOtp');
    Route::post('forgot', 'ForgotController@forgot');
    Route::post('reset', 'ForgotController@reset');
    Route::get('ngn', 'BankController@ngnBanksApi');
    Route::get('getAllUser', 'AuthController@getAllUser');

   Route::middleware(['auth:api'])->group(function () {
    // User Update
    Route::get('getProfile', 'AuthController@getProfile');
    Route::get('logout', 'AuthController@logout');
    Route::put('updateProfile', 'AuthController@updateProfile');
    Route::put('updateUsertype', 'AuthController@updateUsertype');
    Route::post('category', 'ExpenseCategoryController@category');
    
    //Expense
    Route::post('createExpense', 'ExpenseController@createExpense');
    Route::post('userExpense/{expenseId}', 'ExpenseController@inviteUserToExpense');
    Route::put('updateExpense/{id}', 'ExpenseController@updateExpense');
    Route::get('getAllExpenses', 'ExpenseController@getAllExpenses');
    Route::get('getRandomUserExpense/{user_id}', 'ExpenseController@ getRandomUserExpense');
    Route::delete('deleteInvitedExpenseUser/{user_id}', 'ExpenseController@deleteInvitedExpenseUser');
    Route::delete('deleteExpense/{id}', 'ExpenseController@deleteExpense');
    
    //Group
    Route::post('createGroup', 'GroupController@createGroup');
    Route::post('inviteUsersToGroup/{groupId}', 'GroupController@inviteUsersToGroup');
    Route::get('countAllGroupsPerUser', 'GroupController@countAllGroupsPerUser');
    Route::get('getAllGroupsPerUser', 'GroupController@getAllGroupsPerUser');
    Route::get('getRandomUserGroup/{user_id}', 'GroupController@getRandomUserGroup');
    Route::delete('deleteInvitedGroupUser/{user_id}', 'GroupController@deleteInvitedGroupUser');
    Route::delete('deleteGroup/{id}', 'GroupController@deleteGroup');
    
    //Bank
    Route::put('updateBank/{id}', 'BankController@updateBank');
    Route::post('addBank', 'BankController@addBank');
    Route::get('getBankPerUser', 'BankController@getBankPerUser');
    Route::delete('bank/{id}', 'BankController@bank');
    
    //Sub Category
    Route::put('updateSubCategory/{id}', 'ExpenseSubCategoryController@updateSubCategory');
    Route::post('SubCategory', 'ExpenseSubCategoryController@SubCategory');
    Route::get('getSubCateListPerCategory/{category_id}', 'ExpenseSubCategoryController@getSubCateListPerCategory');
    Route::delete('deleteExpenseSubCategory/{id}', 'ExpenseSubCategoryController@deleteExpenseSubCategory');
    
    //Category
    Route::put('updateCategory/{id}', 'ExpenseCategoryController@updateCategory');
    Route::get('allCategoriesPerUser', 'ExpenseCategoryController@allCategoriesPerUser');
    Route::get('getCateList', 'ExpenseCategoryController@getCateList');
    Route::delete('deleteExpenseCategory/{id}', 'ExpenseCategoryController@deleteExpenseCategory');
     
    //Reporting
    Route::get('allExpensesPerUser', 'ExpenseController@allExpensesPerUser');
    Route::get('countExpensesPerUser', 'ExpenseController@countExpensesPerUser');
    Route::post('getUserExpenseWithDate', 'ExpenseController@getUserExpenseWithDate');
    Route::post('getUserGroupWithDate', 'ExpenseController@getUserGroupWithDate');
    Route::post('getUserExpenseWithCategory/{categoryId}', 'ReportingController@getUserExpenseWithCategory');
    Route::post('getUserExpenseWithSubCategory/{sub_categoryId}', 'ReportingController@getUserExpenseWithSubCategory');
    
    
    //Splitting Methods
    Route::post('splitingMethod', 'PaymentSplittingController@splitingMethod');
    Route::get('getSplittingMethods', 'PaymentSplittingController@getSplittingMethods');

    //Complain 
    Route::post('makeComplain', 'ComplainController@makeComplain');
    Route::get('getAllComplains', 'ComplainController@getAllComplains');

    //Admin Routes
    // Route::get('admin', ['middleware' => 'admin', function () {
    //     //
    // }]);

    
    
    
    
   
    
    
    
    //Route::get('listusergroup', 'ExpenseController@listGroupWithDatePerUser');
       });
   });       