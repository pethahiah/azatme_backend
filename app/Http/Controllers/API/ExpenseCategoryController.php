<?php
namespace App\Http\Controllers\API;


use App\ExpenseCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class ExpenseCategoryController extends Controller
{

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
   
   
       public function updateCategory(Request $request, $id)
   {
       $update = ExpenseCategory::find($id);;
       $update->update($request->all());
   
       return $update;
   
   }
   
   public function getCateList()
       {
           $categoryList = ExpenseCategory::all();
           return $categoryList;
       }
           
   public function deleteExpenseCategory($id) 
       {
       $deleteCate = ExpenseCategory::findOrFail($id);
       if($deleteCate)
          $deleteCate->delete(); 
       else
       return response()->json(null); 
   }



}
