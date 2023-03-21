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

class BusinessController extends Controller
{
    //

    public function createBusiness(Request $request)
    {
        
        $generalPath = env('file_public_path');
        $getBusiness = Auth::user();
        $getAd = $getBusiness -> usertype;
            if($getAd === 'merchant')
            {
            $business = new Business([
            'business_name' => $request->business_name,
            'business_email' => $request->business_email,
            'description' => $request->description,
            'type' => $request->type,
            'registration_number' => $request->registration_number,
            'business_code' => Str::random(10),
            'business_address' => $request->business_address,
            'owner_id' => Auth::user()->id,
            'vat_id' => $request->vat_id,
            ]);
                if($request->business_logo && $request->business_logo->isValid())
                {
                $file_name = time().'.'.$request->business_logo->extension();
                $request->business_logo->move(public_path('images'),$file_name);
                $path = "$generalPath/$file_name";
                $business->business_logo = $path;
                }
                
                
                $buses = Business::where('business_email', $request->business_email)->get();
        
        if(sizeof($buses) > 0){
            // tell business not to duplicate same email
            return response([
                'message' => 'business already exists'
            ], 401);
        }

                $business->save();
                return response()->json($business);
                }else{
                return response()->json('You are not authorize to perform this action');
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
