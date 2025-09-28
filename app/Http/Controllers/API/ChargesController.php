<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Charge;


class ChargesController extends Controller
{
    //

    /**
     * @group Charges
     *
     * API endpoints for user Charges management.
     */

    /**
     * Create a new charge.
     *
     * This endpoint allows the creation of a new charge with the specified product and amount.
     *
     * @bodyParam product_affected string required The product that is affected by this charge. Allowed values: all_products, refundme, ajo, kontribute, business. Example: "ajo"
     * @bodyParam charges numeric required The amount of the charge. Example: 100
     *
     * @response 201 {
     *   "message": "Charge created successfully",
     *   "data": {
     *     "id": 1,
     *     "product_affected": "ajo",
     *     "charges": 100
     *   }
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "product_affected": ["The product affected field is required."],
     *     "charges": ["The charges field is required.", "The charges must be a number."]
     *   }
     * }
     *
     * @post /create-charges
     */

    public function createCharges(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'product_affected' => 'required',
            'charges' => 'required|numeric',
        ]);
       // return $validatedData;

        // Create a new charge
        $charge = Charge::create([
            'product_affected' => $validatedData['product_affected'],
            'charges' => $validatedData['charges'],
        ]);

        return response()->json(['message' => 'Charge created successfully', 'data' => $charge], 201);
    }

    /**
     * Retrieve all charges.
     *
     * This endpoint retrieves a list of all charges in the system.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "product_affected": "ajo",
     *       "charges": 100
     *     },
     *     // More charge objects
     *   ]
     * }
     *
     * @get /charges
     */

    public function readCharges(Request $request): \Illuminate\Http\JsonResponse
    {
        // Retrieve all charges
        $charges = Charge::all();

        return response()->json(['data' => $charges], 200);
    }

    /**
     * Delete a specific charge by ID.
     *
     * This endpoint allows the deletion of a charge identified by the provided ID.
     *
     * @urlParam id integer required The ID of the charge to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Charge deleted successfully"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Charge] with id 1"
     * }
     *
     * @delete /charges/{id}
     */

    public function deleteCharge($id): \Illuminate\Http\JsonResponse
    {
        // Find the charge by ID and delete it
        $charge = Charge::findOrFail($id);
        $charge->delete();

        // Return success message
        return response()->json(['message' => 'Charge deleted successfully'], 200);
    }

    /**
     * Update an existing charge.
     *
     * This endpoint allows the updating of a charge identified by the provided ID with the new data.
     *
     * @urlParam id integer required The ID of the charge to update. Example: 1
     * @bodyParam product_affected string The product that is affected by this charge. Allowed values: all_products, refundme, ajo, kontribute, business. Example: "refundme"
     * @bodyParam charges numeric The amount of the charge. Example: 150
     *
     * @response 200 {
     *   "message": "Charge updated successfully",
     *   "data": {
     *     "id": 1,
     *     "product_affected": "refundme",
     *     "charges": 150
     *   }
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "product_affected": ["The selected product affected is invalid."],
     *     "charges": ["The charges must be a number."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Charge] with id 1"
     * }
     *
     * @put /charges/{id}
     */
    public function updateCharge(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        // Find the charge by ID
        $charge = Charge::findOrFail($id);

        // Validate the request data
        $validatedData = $request->validate([
            'product_affected' => 'sometimes|in:all_products,refundme,ajo,kontribute,business',
            'charges' => 'sometimes|numeric',
        ]);

        if ($request->has('product_affected')) {
            $charge->product_affected = $validatedData['product_affected'];
        }

        if ($request->has('charges')) {
            $charge->charges = $validatedData['charges'];
        }

        // Save the updated charge
        $charge->save();

        return response()->json(['message' => 'Charge updated successfully', 'data' => $charge], 200);
    }

    /**
     * Retrieve the last updated charge.
     *
     * This endpoint retrieves the most recently updated charge.
     *
     * @response 200 {
     *   "id": 1,
     *   "product_affected": "ajo",
     *   "charges": 100
     * }
     *
     * @response 404 {
     *   "message": "No charges found"
     * }
     *
     * @get /get-lastUpdated-charges
     */

  public function getLastUpdatedCharge(): \Illuminate\Http\JsonResponse
  {
    // Retrieve the last updated charge
    $lastUpdatedCharge = Charge::latest()->first();
    return response()->json($lastUpdatedCharge);
}


}
