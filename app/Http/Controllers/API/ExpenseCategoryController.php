<?php
namespace App\Http\Controllers\API;


use App\ExpenseCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class ExpenseCategoryController extends Controller
{

    /**
     * @group Expense Category
     *
     * API endpoints for user Expense Category management.
     */
    /**
     * Create a new expense category.
     *
     * This endpoint allows authenticated users to create a new expense category.
     *
     * @bodyParam name string required The name of the expense category. Example: "Travel"
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Travel",
     *   "user_id": 1,
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required.", "The name must be at least 3 characters.", "The name must not be greater than 50 characters.", "The name has already been taken."]
     *   }
     * }
     *
     * @post /category
     */
    public function category(Request $request){

        $this->validate($request, [
               'name' => 'required|min:3|max:50|unique:expense_categories,name'
           ]);


       $category = new ExpenseCategory();
       $category ->name=$request->input('name');
       $category->user_id = $request->user()->id;
       $category -> save();
       return $category;

       }

    /**
     * Update an existing expense category.
     *
     * This endpoint allows authenticated users to update an existing expense category.
     *
     * @urlParam id integer required The ID of the expense category to update. Example: 1
     * @bodyParam name string required The updated name of the expense category. Example: "Food"
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Food",
     *   "user_id": 1,
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [ExpenseCategory] with id 1"
     * }
     *
     * @put /updateCategory/{id}
     */
       public function updateCategory(Request $request, $id)
   {
       $update = ExpenseCategory::find($id);;
       $update->update($request->all());

       return $update;

   }

    /**
     * Get all expense categories.
     *
     * This endpoint retrieves a list of all expense categories.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Travel",
     *       "user_id": 1,
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More category objects
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No categories found"
     * }
     *
     * @get /getCateList
     */
   public function getCateList()
       {
           $categoryList = ExpenseCategory::all();
           return $categoryList;
       }

    /**
     * Delete a specific expense category.
     *
     * This endpoint allows authenticated users to delete a specific expense category.
     *
     * @urlParam id integer required The ID of the expense category to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Expense category deleted successfully"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [ExpenseCategory] with id 1"
     * }
     *
     * @delete /deleteExpenseCategory/{id}
     */
   public function deleteExpenseCategory($id)
       {
       $deleteCate = ExpenseCategory::findOrFail($id);
       if($deleteCate)
          $deleteCate->delete();
       else
       return response()->json(null);
   }



}
