<?php

namespace App\Http\Controllers\API;

use App\DirectDebitMandate;
use App\DirectDebitProduct;
use App\Http\Controllers\Controller;
use App\Ajo;
use Carbon\Carbon;
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

    /**
     * Create a new direct debit mandate.
     *
     * This endpoint allows the creation of a new direct debit mandate based on the provided details.
     *
     * @bodyParam productId integer required The ID of the product. Example: 123
     * @bodyParam productName string required The name of the product. Example: "Premium Membership"
     * @bodyParam remarks string required Any additional remarks. Example: "Monthly subscription."
     * @bodyParam accountNumber string required The bank account number. Example: "1234567890"
     * @bodyParam bankCode string required The bank code. Example: "ABC123"
     * @bodyParam accountName string required The name of the account holder. Example: "John Doe"
     * @bodyParam homeAddress string required The address of the account holder. Example: "123 Main St, Anytown"
     * @bodyParam description string The description of the mandate. Example: "Monthly subscription for premium services."
     * @bodyParam paymentFrequency string required The frequency of the payment. Example: "Monthly"
     * @bodyParam inviteLink string required The invitation link. Example: "http://example.com/invite"
     * @bodyParam bankName string required The name of the bank. Example: "Example Bank"
     * @bodyParam startDate string required The start date of the mandate in YYYY-MM-DD format. Example: "2024-01-01"
     * @bodyParam endDate string required The end date of the mandate in YYYY-MM-DD format. Example: "2024-12-31"
     *
     * @response 201 {
     *   "success": true,
     *   "product": {
     *     "id": 1,
     *     "productId": 123,
     *     "productName": "Premium Membership",
     *     "remarks": "Monthly subscription.",
     *     "accountNumber": "1234567890",
     *     "bankCode": "ABC123",
     *     "accountName": "John Doe",
     *     "homeAddress": "123 Main St, Anytown",
     *     "description": "Monthly subscription for premium services.",
     *     "paymentFrequency": "Monthly",
     *     "inviteLink": "http://example.com/invite",
     *     "bankName": "Example Bank",
     *     "startDate": "2024-01-01",
     *     "endDate": "2024-12-31"
     *   }
     * }
     *
     * @response 400 {
     *   "error": {
     *     "productId": ["The product id field is required."],
     *     "productName": ["The product name field is required."],
     *     "remarks": ["The remarks field is required."],
     *     "accountNumber": ["The account number field is required."],
     *     "bankCode": ["The bank code field is required."],
     *     "accountName": ["The account name field is required."],
     *     "homeAddress": ["The home address field is required."],
     *     "paymentFrequency": ["The payment frequency field is required."],
     *     "inviteLink": ["The invite link field is required."],
     *     "bankName": ["The bank name field is required."],
     *     "startDate": ["The start date field is required."],
     *     "endDate": ["The end date field is required."]
     *   }
     * }
     *
     * @response 404 {
     *   "error": "Could not find this ajo transaction"
     * }
     *
     * @response 500 {
     *   "error": "Failed to add product. Please try again later."
     * }
     *
     * @post /create-dd-mandate
     */

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
                //  'fileName' => 'nullable',
                'description' => 'nullable',
                //  'fileExtension' => 'nullable',
                'paymentFrequency' => 'required',
                'inviteLink' => 'required|url',
                'bankName' => 'required|string',
                'startDate' => 'required|string',
                'endDate' => 'required|string',

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

            // Call the service to create the mandate
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

    /**
     * Update an existing direct debit mandate.
     *
     * This endpoint updates an existing direct debit mandate based on the provided details.
     *
     * @bodyParam mandateId integer required The ID of the mandate to update. Example: 1
     * @bodyParam requestType string required The type of request. Allowed values: Suspend, Enable, Update. Example: "Update"
     *
     * @response 200 {
     *   "message": "Mandate updated successfully",
     *   "mandate": {
     *     "id": 1,
     *     "status": "Updated"
     *   }
     * }
     *
     * @response 400 {
     *   "error": {
     *     "mandateId": ["The mandate id field is required."],
     *     "requestType": ["The request type field is required.", "The selected request type is invalid."]
     *   }
     * }
     *
     * @response 500 {
     *   "error": "Failed to update mandate. Please try again later."
     * }
     *
     * @post /update-dd-mandate
     */

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

    /**
     * Retrieve a list of direct debit banks.
     *
     * This endpoint retrieves the list of banks available for direct debit transactions.
     *
     * @response 200 {
     *   "banks": [
     *     {
     *       "code": "ABC123",
     *       "name": "Example Bank"
     *     }
     *   ]
     * }
     *
     * @response 500 {
     *   "error": "Failed to retrieve bank list. Please try again later."
     * }
     *
     * @post /get-dd-bankList
     */

    public function getDDBankList(): ?\Illuminate\Http\JsonResponse
    {
        // Call BankListService to add the product
        return $this->directDebitService->getBankList();
    }


    /**
     * Retrieve a list of products available for direct debit.
     *
     * This endpoint retrieves all products available for direct debit transactions.
     *
     * @response 200 {
     *   "products": [
     *     {
     *       "id": 1,
     *       "name": "Premium Membership",
     *       "description": "Access to premium features."
     *     }
     *   ]
     * }
     *
     * @response 500 {
     *   "message": "Failed to retrieve product list. Please try again later."
     * }
     *
     * @post /productlist
     */

    public function productlist(): \Illuminate\Http\JsonResponse
    {
        $productList = $this->directDebitService->getProductList();

        if (isset($productList['error'])) {
            return response()->json(['message' => $productList['error']], 500);
        }

        return response()->json($productList, 200);
    }


    public function initiateDirectDebitRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'recipient' => 'required|string|email',
            'expiryDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'amountLimit' => 'required|numeric',
            'billingCycle' => 'required|string',
            'description' => 'required|string',
            'productId' => 'required|integer',
            'serviceReference' => 'required|string',
            'packageId' => 'required|integer',
            'bankCode' => 'required|string',
            'mandateAccountName' => 'required|string',
            'phoneNumber' => 'required|string',
            'mandateAccountNumber' => 'required|string',
            'payerAddress' => 'required|string',
            'creditAccountNumber' => 'required|string',
            'creditAccountName' => 'required|string',
            'preferredCompletionOptions' => 'required|string',
            'payerName' => 'required|string'
        ]);

        // Call the service to handle the business logic
        $result = $this->directDebitService->initiateDirectDebitRequest($validatedData);

        // Return the appropriate response based on the service result
        if ($result['status']) {
            return response()->json($result['data'], 200);
        } else {
            return response()->json(['message' => $result['message']], $result['code']);
        }
    }

}
