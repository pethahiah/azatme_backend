<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use app\user;

class ComplainController extends Controller
{
    //
Public function makeComplain()
{
        $complain = Expense::create([
        'expense_name'=> $request->expense_name,
        'description' => $request->description,
        'complain_reference_code'=> $request->unique_code,
        'severity' => $request->severity, //high,medium,low
        'user_id' => Auth::user()->id,
             
        ]);

        //if(Auth::user())
        
       // $getUniqueCode = expense::->where('uique_code', Auth::user()->user_id->uique_code))->first();
    
        
        return response()->json($complain);
        
        
    }

        public function getAllComplains()
        {
            $getAllComplains = complain::all();
            return $getAllComplains;
        }
}
