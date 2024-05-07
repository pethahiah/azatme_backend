<?php

namespace App\Http\Controllers\API;

use App\Ajo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use app\Services\DirectDebitService;
use Illuminate\Support\Facades\Validator;


class DirectDebitController extends Controller
{
    //
    protected $directDebitService;

    public function __construct(DirectDebitService $directDebitService)
    {
        $this->directDebitService = $directDebitService;
    }

    public function addProduct(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'productName' => 'required|string|max:255',
                'productDescription' => 'required|string',
            ]);

            // Call ProductService to add the product
            $product = $this->directDebitService->addProduct($validatedData);

            return response()->json(['success' => true, 'product' => $product], 201);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error adding product: ' . $e->getMessage());

            // Return error response
            return response()->json(['error' => 'Failed to add product. Please try again later.'], 500);
        }
    }

    public function createMandate(Request $request, $ajoId): \Illuminate\Http\JsonResponse
    {
        try {
            // Define validation rules
            $rules = [
                'productId' => 'required',
                'productName' => 'required',
                'remarks' => 'nullable',
                'paymentAmount' => 'required|numeric',
                'customer_phone' => 'required|numeric',
                'accountNumber' => 'required|numeric',
                'bankCode' => 'required|numeric',
                'accountName' => 'required',
                'phoneNumber' => 'required|numeric',
                'homeAddress' => 'required',
                'fileName' => 'nullable',
                'description' => 'nullable',
                'fileBase64String' => 'nullable',
                'fileExtension' => 'nullable',
                'paymentFrequency' => 'required',
                'packageId' => 'required',
                'collectionAccountNumber' => 'required|numeric',
                'mandateType' => 'required',
                'routingOption' => 'required',
            ];

            // Perform validation
            $validator = Validator::make($request->all(), $rules);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $ajo = Ajo::where([
                ['user_id', '=', Auth::user()->getAuthIdentifier()],
                ['id', '=', $ajoId]
            ])->first();
            if (!$ajo) {
                return response()->json("Could not find this ajo transaction", 404);
            }

            // Call ProductService to add the product
            $validatedData = $validator->validated();
            $product = $this->directDebitService->createMandate($validatedData, $ajo);

            return response()->json(['success' => true, 'product' => $product], 201);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error adding product: ' . $e->getMessage());

            // Return error response
            return response()->json(['error' => 'Failed to add product. Please try again later.'], 500);
        }
    }

    public function updateMandate(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'mandateId' => 'required|integer',
                'requestType' => 'required|in:Suspend,Enable,Update',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $requestData = $request->all();
            $mandate = $this->directDebitService->updateMandate($requestData);
            return response()->json(['message' => 'Mandate updated successfully', 'mandate' => $mandate], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDDBankList(): ?\Illuminate\Http\JsonResponse
    {
        // Call BankListService to add the product
        return $this->directDebitService->getBankList();
    }

}
