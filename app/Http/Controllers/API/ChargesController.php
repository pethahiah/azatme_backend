<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Charge;


class ChargesController extends Controller
{
    //
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

    public function readCharges(Request $request): \Illuminate\Http\JsonResponse
    {
        // Retrieve all charges
        $charges = Charge::all();

        return response()->json(['data' => $charges], 200);
    }


    public function deleteCharge($id): \Illuminate\Http\JsonResponse
    {
        // Find the charge by ID and delete it
        $charge = Charge::findOrFail($id);
        $charge->delete();

        // Return success message
        return response()->json(['message' => 'Charge deleted successfully'], 200);
    }


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
    
  public function getLastUpdatedCharge()
{
    // Retrieve the last updated charge
    $lastUpdatedCharge = Charge::latest()->first();
    return response()->json($lastUpdatedCharge);
}


}
