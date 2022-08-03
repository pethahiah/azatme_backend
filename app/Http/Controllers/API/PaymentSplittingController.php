<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\splittingMethod;

class PaymentSplittingController extends Controller
{
    //

    public function splitingMethod(Request $request)
    {
        $split = new splittingMethod();
        $split ->split=$request->input('split');
        $split->user_id = $request->user()->id;
        $split -> save();
       return $split;
   
    }

    public function getSplittingMethods()
    {
        $getSplitMethod = splittingMethod::all();
        return $getSplitMethod;
    }


}
