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

    /**
     * @group Bank Management
     *
     * Create a new business.
     *
     * This endpoint allows authenticated users with the role of 'merchant' to create a new business.
     *
     * @bodyParam business_name string required The name of the business. Example: "Tech Innovators"
     * @bodyParam business_email string required The email of the business. Example: "contact@techinnovators.com"
     * @bodyParam description string The description of the business. Example: "Leading provider of tech solutions."
     * @bodyParam type string The type of the business. Example: "Retail"
     * @bodyParam registration_number string The registration number of the business. Example: "123456789"
     * @bodyParam business_address string The address of the business. Example: "123 Tech Street"
     * @bodyParam vat_option boolean Whether VAT is applicable. Example: true
     * @bodyParam vat_id string The VAT ID of the business. Example: "VAT123456"
     * @bodyParam business_logo file The logo of the business. Example: [file]
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Business created successfully.",
     *   "data": {
     *     "business_name": "Tech Innovators",
     *     "business_email": "contact@techinnovators.com",
     *     "description": "Leading provider of tech solutions.",
     *     "type": "Retail",
     *     "registration_number": "123456789",
     *     "business_address": "123 Tech Street",
     *     "vat_option": true,
     *     "vat_id": "VAT123456",
     *     "business_logo": "http://example.com/storage/profiles/logo.jpg"
     *   }
     * }
     *
     * @response 409 {
     *   "status": "error",
     *   "message": "Business already exists"
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @post /createBusiness
     */


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

    /**
     * Update an existing business.
     *
     * This endpoint allows authenticated users with the role of 'merchant' to update business details.
     *
     * @urlParam id integer required The ID of the business to update. Example: 1
     * @bodyParam customer_name string required The new name of the customer. Example: "Jane Doe"
     * @bodyParam customer_address string required The new address of the customer. Example: "456 Innovation Road"
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Business updated successfully.",
     *   "data": {
     *     "id": 1,
     *     "customer_name": "Jane Doe",
     *     "customer_address": "456 Innovation Road"
     *   }
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @put /update-business/{id}
     */

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

    /**
     * Retrieve all businesses owned by the authenticated user.
     *
     * This endpoint allows authenticated users with the role of 'merchant' to retrieve all their businesses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "business_name": "Tech Innovators",
     *       "business_email": "contact@techinnovators.com",
     *       "description": "Leading provider of tech solutions.",
     *       "type": "Retail",
     *       "registration_number": "123456789",
     *       "business_address": "123 Tech Street",
     *       "vat_option": true,
     *       "vat_id": "VAT123456",
     *       "business_logo": "http://example.com/storage/profiles/logo.jpg"
     *     }
     *   ]
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @get /getAllBusiness
     */

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

    /**
     * Retrieve a single business by its code.
     *
     * This endpoint allows authenticated users with the role of 'merchant' to retrieve a specific business.
     *
     * @urlParam business_code string required The code of the business to retrieve. Example: "TECH123"
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "id": 1,
     *     "business_name": "Tech Innovators",
     *     "business_email": "contact@techinnovators.com",
     *     "description": "Leading provider of tech solutions.",
     *     "type": "Retail",
     *     "registration_number": "123456789",
     *     "business_address": "123 Tech Street",
     *     "vat_option": true,
     *     "vat_id": "VAT123456",
     *     "business_logo": "http://example.com/storage/profiles/logo.jpg"
     *   }
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Business not found."
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @get /get-a-single-business-under-owner/{business_code}
     */

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

    /**
     * Delete a business.
     *
     * This endpoint allows authenticated users with the role of 'merchant' to delete a specific business.
     *
     * @urlParam id integer required The ID of the business to delete. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Business deleted successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Business not found."
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @delete /delete-a-business/{id}
     */


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
