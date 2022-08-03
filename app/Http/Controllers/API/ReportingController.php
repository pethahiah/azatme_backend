<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    //

public function countAllGroupsPerUser()
{
$getAuthUser = Auth::user();
$getUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->count();
return response()->json($getUserGroups);

}

public function getAllGroupsPerUser()
{

$getAuthUser = Auth::user();
$countUserGroups = UserGroup::where('reference_id', $getAuthUser->id)->get();
return response()->json($countUserGroups);

}

public function getUserExpenseWithDate(Request $request)
    {
       $start_date = Carbon::parse($request->from)
                             ->toDateTimeString();

       $end_date = Carbon::parse($request->to)
                             ->toDateTimeString();

        $getAuthUser = Auth::user();
       return UserExpense::whereBetween('created_at',[$start_date,$end_date])->where('principal_id', $getAuthUser->id)->get();

    }
    
public function getUserGroupWithDate(Request $request)
    {
       $start_date = \Carbon\Carbon::parse($request->from)
                             ->format('Y-m-d'); 

       $end_date = \Carbon\Carbon::parse($request->to)
                             ->format('Y-m-d');

        $getAuthUser = Auth::user();
       return UserGroup::whereBetween('created_at',[$start_date,$end_date])->where('reference_id', $getAuthUser->id)->get();

    }
}
