<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BusinessRequest;
use App\Business;
use Illuminate\Support\Str;
use Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;



class BusinessController extends Controller
{
    //

public function createBusiness(Request $request)
    {
    $user = Auth::user();
    if ($user->usertype === 'merchant') {
    $business = new Business();
    $business->business_name = $request->business_name;
    $business->business_email = $request->business_email;
    $business->description = $request->description;
    $business->type = $request->type;
    $business->registration_number = $request->registration_number;
    $business->business_code = Str::random(10);
    $business->business_address = $request->business_address;
    $business->owner_id = $user->id;
    $business->vat_option = $request->vat_option;
    $business->vat_id = $request->vat_id;

    $path = null; // Initialize path variable
   
    if ($request->hasFile('business_logo') && $request->file('business_logo')->isValid()) {
        $file = $request->file('business_logo')->store('profiles', 'public');
        $hashedFilename = $request->file('business_logo')->hashName();
        $business->business_logo = url('storage/profiles/' . $hashedFilename);
        $path = public_path('storage/profiles/' . $hashedFilename);
    }

    $existingBusinesses = Business::where('business_email', $request->business_email)->get();

    if ($existingBusinesses->count() > 0) {
        return response()->json(['message' => 'Business already exists'], 409);
    }

    $business->save();

    return response()->json([$business->business_logo, $business], 200);
} else {
    return response()->json('You are not authorized to perform this action');
}

    }


    public function updateBusiness(Request $request, $id)
    {
       // return response()->json($request->input());
        $getBusiness = Auth::user();
        $getAd = $getBusiness -> usertype;
            if($getAd === 'merchant')
            {
            $getId = Business::where('id', $id)->first();
            $getId->customer_name = $request['customer_name'];
            $getId->customer_address = $request['customer_address'];
            $getId->save();
            return response()->json($getId);
            }
            else{
            return response()->json('You are not authorize to perform this action');
            }

    }
    
    public function getAllBusiness()
    {
        $getBusiness = Auth::user();
        $getAd = $getBusiness -> usertype;
            if($getAd === 'merchant')
            {
            $getAllBusiness = Business::where('owner_id', $getBusiness->id)->get();
           // log::channel('slack')->info('about to get all businesses');
            
            return response()->json($getAllBusiness);
            }else{
            return response()->json('You are not authorize to perform this action');
            }

    }
    
    public function getABusiness($business_code)
    {
        $getBusiness = Auth::user();
        $getAd = $getBusiness -> usertype;
            if($getAd === 'merchant')
            {
            $getABusiness = Business::where('business_code', $business_code)->first();
            return response()->json($getABusiness);
            }else{
            return response()->json('You are not authorize to perform this action');
            }
    }
    
    
    
    
    public function deleteABusiness($id)
        {
        $deleteBusiness = Business::findOrFail($id);
        $getBusiness = Auth::user();
        $getAd = $getBusiness -> usertype;
            if($getAd === 'merchant')
            {
            $getDeletedbus = Business::where('owner_id', Auth::user()->id);
            $deleteBusiness->delete();
            }else{
            return response()->json('You are not authorize to perform this action');
            }

        }
        
        
}
