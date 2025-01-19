<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ExpenseSubCategory;
use App\ExpenseCategory;

class ExpenseSubCategoryController extends Controller
{
    //

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
   
   
       public function updateSubCategory(Request $request, $id)
       {
           $update = ExpenseSubCategory::find($id);;
           $update->update($request->all());
   
           return response()->json($update);
       
       }
   
   
   
   public function getSubCateListPerCategory($category_id)
       {
           $SubcategoryList = ExpenseSubCategory::where('category_id', $category_id)->get();
           return response()->json( $SubcategoryList);
       }
   
   
   
       public function deleteExpenseSubCategory($id) 
       {
       $deleteSubCate = ExpenseSubCategory::findOrFail($id);
       if($deleteSubCate)
          $deleteSubCate->delete(); 
       else
       return response()->json(null); 
   }



}
