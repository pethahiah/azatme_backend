<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use App\Customer;
use App\Business;
use Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    //
    /**
     * @group Customer
     *
     * API endpoints for customer management.
     */

    /**
     * Create a new customer.
     *
     * This endpoint allows authenticated users to create a new customer under a specific business.
     *
     * @urlParam business_code string required The unique code of the business. Example: "BUS1234"
     * @bodyParam customer_name string required The name of the customer. Example: "Jane Doe"
     * @bodyParam customer_email string required The email of the customer. Example: "jane.doe@example.com"
     * @bodyParam customer_phone string required The phone number of the customer. Example: "+1234567890"
     *
     * @response 201 {
     *   "id": 1,
     *   "customer_name": "Jane Doe",
     *   "customer_email": "jane.doe@example.com",
     *   "customer_phone": "+1234567890",
     *   "customer_code": "BUS1234",
     *   "owner_id": 1,
     *   "flagged": 0,
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 409 {
     *   "message": "User already exists with this business code"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "customer_name": ["The customer name field is required."],
     *     "customer_email": ["The customer email field is required."],
     *     "customer_phone": ["The customer phone field is required."]
     *   }
     * }
     *
     * @post /create-customer/{business_code}
     */
    public function createCustomer(Request $request, $business_code)
{
    $business = Business::where('business_code', $business_code)->first();
//	return $business->business_code;

   // Check if customer already exist
$existingCustomer = Customer::where('customer_code', $business->business_code)->where('customer_email', $request->customer_email)->first();
//     $existingCustomer = Customer::where(['customer_code' => $business_code, 'customer_email' => $request->customer_email)->first();
	 if ($existingCustomer) {
            return response([
                'message' => 'User already exists with this business code'
            ], 409);
        }

    // Check if a user with the given email, name, and phone already exists
    $customerFlagged = Customer::where([
        'customer_name' => $request->customer_name,
        'customer_email' => $request->customer_email,
        'customer_phone' => $request->customer_phone,
	'customer_code' => $business_code
    ])->first();

    if ($customerFlagged) {
        // Customer already exists, flag it
        $customerFlagged->flagged = 1;
        $customerFlagged->save();

        return response()->json($customerFlagged);
    }

    // Create a new customer
    $customer = new Customer([
        'customer_name' => $request->customer_name,
        'customer_email' => $request->customer_email,
        'customer_phone' => $request->customer_phone,
        'customer_code' => $business->business_code,
        'owner_id' => Auth::user()->id,
    ]);

    $customer->save();
    return response()->json($customer);
}

    /**
     * Update an existing customer.
     *
     * This endpoint allows users to update customer information.
     *
     * @urlParam id integer required The ID of the customer to update. Example: 1
     * @bodyParam customer_name string required The updated name of the customer. Example: "Jane Doe"
     * @bodyParam customer_phone string required The updated phone number of the customer. Example: "+1234567890"
     *
     * @response 200 {
     *   "id": 1,
     *   "customer_name": "Jane Doe",
     *   "customer_email": "jane.doe@example.com",
     *   "customer_phone": "+1234567890",
     *   "customer_code": "BUS1234",
     *   "owner_id": 1,
     *   "flagged": 0,
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "customer_name": ["The customer name field is required."],
     *     "customer_phone": ["The customer phone field is required."]
     *   }
     * }
     *
     * @response 403 {
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @put /update-customer/{id}
     */

public function updateCustomer(Request $request, $id)
{
    $getAdmin = Auth::user();

    if ($getAdmin->usertype === 'merchant') {
        // Find the customer by ID
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['error' => 'Customer not found.'], 404);
        }

        // Update only the fields present in the request
        $allowedFields = ['customer_name', 'customer_phone'];
        $updateData = $request->only($allowedFields);

        if (empty($updateData)) {
            return response()->json(['message' => 'No valid fields provided for update.'], 400);
        }

        $customer->update($updateData);

        return response()->json([
            'message' => 'Customer updated successfully.',
            'data' => $customer
        ], 200);
    } else {
        return response()->json(['error' => 'You are not authorized to perform this action.'], 403);
    }
}

    /**
     * Get all customers under a specific business.
     *
     * This endpoint retrieves a list of all customers for a specific business.
     *
     * @urlParam business_code string required The unique code of the business. Example: "BUS1234"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer_name": "Jane Doe",
     *       "customer_email": "jane.doe@example.com",
     *       "customer_phone": "+1234567890",
     *       "customer_code": "BUS1234",
     *       "owner_id": 1,
     *       "flagged": 0,
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More customer objects
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No customers found for this business code"
     * }
     *
     * @get /gac-under-a-specific-business/{business_code}
     */

   public function getAllCustomersUnderABusiness($business_code)
    {

        $getAllCustomer = Customer::where('customer_code', $business_code)->latest()->get();
        return response()->json($getAllCustomer);

    }

    /**
     * List all customers for a specific owner.
     *
     * This endpoint retrieves a list of all customers for a specific owner.
     *
     * @urlParam owner_id integer required The ID of the owner. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer_name": "Jane Doe",
     *       "customer_email": "jane.doe@example.com",
     *       "customer_phone": "+1234567890",
     *       "customer_code": "BUS1234",
     *       "owner_id": 1,
     *       "flagged": 0,
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More customer objects
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No customers found for this owner"
     * }
     *
     * @get /get-customers-under-a-business/{owner_id}
     */
    public function listAllCustomer($owner_id)
    {

        $owner = Business::where('owner_id', $owner_id)->select('owner_id')->first()->owner_id;
        $getAllCustomer = Customer::where('owner_id', $owner)->latest()->get();
        return response()->json($getAllCustomer);

    }

    /**
     * Delete a specific customer.
     *
     * This endpoint allows users to delete a specific customer.
     *
     * @urlParam id integer required The ID of the customer to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Customer deleted successfully"
     * }
     *
     * @response 403 {
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Customer] with id 1"
     * }
     *
     * @delete /delete-a-customer/{id}
     */
     public function deleteACustomer($id)
        {
        $deleteCustomer = Customer::findOrFail($id);
        $getAdmin = Auth::user();
        $getAd = $getAdmin -> usertype;
            if($getAd === 'merchant')
            {
            $deleteCustomer->delete();
            }else{
            return response()->json('You are not authorize to perform this action');
            }

        }

    /**
     * Get all customers.
     *
     * This endpoint retrieves a list of all customers in the system.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer_name": "Jane Doe",
     *       "customer_email": "jane.doe@example.com",
     *       "customer_phone": "+1234567890",
     *       "customer_code": "BUS1234",
     *       "owner_id": 1,
     *       "flagged": 0,
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More customer objects
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No customers found"
     * }
     *
     * @get /get-all-customers
     */
   public function getAllCustomers()
    {
        $getAllCustomer = Customer::get();
        return response()->json($getAllCustomer);

    }


}
