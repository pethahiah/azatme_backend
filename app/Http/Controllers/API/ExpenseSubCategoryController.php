<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ExpenseSubCategory;
use App\ExpenseCategory;

class ExpenseSubCategoryController extends Controller
{
    //

    /**
     * Create a new expense subcategory.
     *
     * @group Expense Subcategories
     *
     * @post /SubCategory
     *
     * @bodyParam name string required The name of the subcategory. Must be unique and between 3 to 50 characters. Example: "Food"
     * @bodyParam category_id int required The ID of the parent category. Example: 1
     *
     * @response 200 {
     *     "success": true,
     *     "ExpenseSubCategory": {
     *         "id": 1,
     *         "name": "Food",
     *         "user_id": 1,
     *         "category_id": 1,
     *         "created_at": "2024-08-20T12:34:56.000000Z",
     *         "updated_at": "2024-08-20T12:34:56.000000Z"
     *     }
     * }
     * @response 422 {
     *     "message": {
     *         "name": [
     *             "The name has already been taken."
     *         ]
     *     }
     * }
     */

    public function SubCategory(Request $request){

        $this->validate($request, [
               'name' => 'required|min:3|max:50|unique:expense_sub_categories,name'
           ]);

       $Subcategory = new ExpenseSubCategory();
       $Subcategory->name=$request->input('name');
       $Subcategory->user_id = $request->user()->id;
       $Subcategory->category_id = $request->category_id;
       $Subcategory -> save();
       return response()->json(['success' => true, $Subcategory]);
       }

    /**
     * Update an existing expense subcategory.
     *
     * @group Expense Subcategories
     *
     * @put /updateSubCategory/{id}
     *
     * @urlParam id int required The ID of the subcategory to update. Example: 1
     * @bodyParam name string The new name of the subcategory. Example: "Groceries"
     * @bodyParam category_id int The ID of the new parent category. Example: 2
     *
     * @response 200 {
     *     "id": 1,
     *     "name": "Groceries",
     *     "user_id": 1,
     *     "category_id": 2,
     *     "created_at": "2024-08-20T12:34:56.000000Z",
     *     "updated_at": "2024-08-20T12:34:56.000000Z"
     * }
     * @response 404 {
     *     "message": "Not found"
     * }
     */

       public function updateSubCategory(Request $request, $id)
       {
           $update = ExpenseSubCategory::find($id);;
           $update->update($request->all());

           return response()->json($update);

       }

    /**
     * Retrieve all subcategories for a specific category.
     *
     * @group Expense Subcategories
     *
     * @get /getSubCateListPerCategory/{category_id}
     *
     * @urlParam category_id int required The ID of the category to retrieve subcategories for. Example: 1
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "name": "Food",
     *         "user_id": 1,
     *         "category_id": 1,
     *         "created_at": "2024-08-20T12:34:56.000000Z",
     *         "updated_at": "2024-08-20T12:34:56.000000Z"
     *     },
     *     {
     *         "id": 2,
     *         "name": "Drinks",
     *         "user_id": 1,
     *         "category_id": 1,
     *         "created_at": "2024-08-20T12:34:56.000000Z",
     *         "updated_at": "2024-08-20T12:34:56.000000Z"
     *     }
     * ]
     * @response 404 {
     *     "message": "Category not found"
     * }
     */

   public function getSubCateListPerCategory($category_id)
       {
           $SubcategoryList = ExpenseSubCategory::where('category_id', $category_id)->get();
           return response()->json( $SubcategoryList);
       }

    /**
     * Delete a specific expense subcategory.
     *
     * @group Expense Subcategories
     *
     * @delete /deleteExpenseSubCategory/{id}
     *
     * @urlParam id int required The ID of the subcategory to delete. Example: 1
     *
     * @response 200 {
     *     "message": "Subcategory deleted successfully."
     * }
     * @response 404 {
     *     "message": "Subcategory not found"
     * }
     */

       public function deleteExpenseSubCategory($id)
       {
       $deleteSubCate = ExpenseSubCategory::findOrFail($id);
       if($deleteSubCate)
          $deleteSubCate->delete();
       else
       return response()->json(null);
   }



}
