<?php

namespace App\Http\Controllers\API;

use App\DirectDebitProduct;
use App\Http\Controllers\Controller;
use App\Ajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\DirectDebit;
use Illuminate\Support\Facades\Validator;


class DirectDebitController extends Controller
{
    //
    protected $directDebitService;

    public function __construct(DirectDebit $directDebitService)
    {
        $this->directDebitService = $directDebitService;
    }

    public function addProduct(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
           //  Validate the incoming request
            // Validate the request data
            $request->validate([
                'productName' => 'required',
                'productDescription' => 'required',
            ]);
            // Retrieve productName and productDescription from the request
            $productName = $request->input('productName');
            $productDescription = $request->input('productDescription');

            // Call ProductService to add the product
            $requestData = $request->only(['productName', 'productDescription']);
            $product = $this->directDebitService->addProduct($productName, $productDescription);
            Log::info('product:'.$product);
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
                'remarks' => 'required',
                'accountNumber' => 'required|numeric',
                'bankCode' => 'required|numeric',
                'accountName' => 'required',
                'homeAddress' => 'required',
                'fileName' => 'nullable',
                'description' => 'nullable',
               // 'fileBase64String' => 'nullable',
               'fileExtension' => 'nullable',
                'paymentFrequency' => 'required',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
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
         //   return response()->json(['success' => true, 'product' =>  $validatedData], 201);
           // Log::info('Response from paythru API: ' . $validatedData);
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

    public function productlist(): \Illuminate\Http\JsonResponse
    {
        $productList = $this->directDebitService->getProductList();

        if (isset($productList['error'])) {
            return response()->json(['message' => $productList['error']], 500);
        }

        return response()->json($productList, 200);
    }

}
