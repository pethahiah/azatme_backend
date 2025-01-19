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
 * @group Direct Debits Management
 *
 * API endpoints for managing direct debit mandates and related operations.
 * 
 * Create a new direct debit mandate.
 *
 * This endpoint allows the creation of a new direct debit mandate based on the provided details.
 * 
 * @urlParam ajoId int required The ID of the ajo. Example: 1
 * 
 * @bodyParam productId string required The ID of the product. Example: 1325355778
 * @bodyParam productName string required The name of the product. Example: "This is my first mandate"
 * @bodyParam remarks string required Any additional remarks. Example: "This is my first mandate"
 * @bodyParam accountNumber string required The bank account number. Example: "2075579177"
 * @bodyParam bankCode string required The bank code. Example: "000004"
 * @bodyParam accountName string required The name of the account holder. Example: "Ogedengbe Sunday Olaoluwa"
 * @bodyParam homeAddress string required The address of the account holder. Example: "1 Adisa Street"
 * @bodyParam description string The description of the mandate. Example: "Good good"
 * @bodyParam paymentFrequency string required The frequency of the payment. Example: "Monthly"
 * @bodyParam startDate string required The start date of the mandate in MM/DD/YYYY format. Example: "07/20/2024"
 * @bodyParam endDate string required The end date of the mandate in MM/DD/YYYY format. Example: "08/20/2024"
 * @bodyParam email string required The email of the account holder. Example: "sunday4oged@yahoo.com"
 * @bodyParam phoneNumber string required The phone number of the account holder. Example: "2348130288477"
 * @bodyParam paymentAmount string required The payment amount for the mandate. Example: "200"
 * @bodyParam serviceReference string required The service reference for the transaction. Example: "17190045401325355778"
 * @bodyParam referenceCode string The reference code for the transaction. Example: "0sABr9PTfS"
 * @bodyParam mandateType string The type of mandate. Example: "Instance"
 * @bodyParam routingOption string The routing option for the mandate. Example: "Default"
 * @bodyParam fileName string The name of the attached file. Example: "mandate.jpg"
 * @bodyParam fileExtension string The extension of the attached file. Example: "jpg"
 * @bodyParam collectionAccountNumber string The account number where the funds will be collected. Example: "2023011710505922352126447149592515"
 * 
 * @response 201 {
 *   "success": true,
 *   "product": {
 *     "productId": "1325355778",
 *     "productName": "This is my first mandate",
 *     "remarks": "This is my first mandate",
 *     "paymentAmount": "200",
 *     "serviceReference": "17190045401325355778",
 *     "accountNumber": "2075579177",
 *     "bankCode": "000004",
 *     "accountName": "Ogedengbe Sunday Olaoluwa",
 *     "phoneNumber": "2348130288477",
 *     "homeAddress": "1 Adisa Street",
 *     "fileName": {},
 *     "description": "Good good",
 *     "fileExtension": "jpg",
 *     "email": "sunday4oged@yahoo.com",
 *     "startDate": "07/20/2024",
 *     "endDate": "08/20/2024",
 *     "paymentFrequency": "Monthly",
 *     "referenceCode": "0sABr9PTfS",
 *     "collectionAccountNumber": "2023011710505922352126447149592515",
 *     "mandateType": "Instance",
 *     "routingOption": "Default",
 *     "updated_at": "2024-06-21T21:16:05.000000Z",
 *     "created_at": "2024-06-21T21:15:40.000000Z",
 *     "id": 22,
 *     "mandateId": 2
 *   }
 * }
 *
 * @response 400 {
 *   "error": {
 *     "productId": ["The product ID field is required."],
 *     "productName": ["The product name field is required."],
 *     "remarks": ["The remarks field is required."],
 *     "accountNumber": ["The account number field is required."],
 *     "bankCode": ["The bank code field is required."],
 *     "accountName": ["The account name field is required."],
 *     "homeAddress": ["The home address field is required."],
 *     "paymentFrequency": ["The payment frequency field is required."],
 *     "startDate": ["The start date field is required."],
 *     "endDate": ["The end date field is required."]
 *   }
 * }
 *
 * @response 404 {
 *   "error": "Could not find this ajo transaction."
 * }
 *
 * @response 500 {
 *   "error": "Failed to create the direct debit mandate. Please try again later."
 * }
 *
 * @post /create-dd-mandate/{ajoId}
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
 * Retrieve all mandates created by the specified user.
 * @group Direct Debits Management
 * This endpoint returns all mandates that are associated with the user.
 *
 * @endpoint GET /api/user/mandates
 *
 * @response 200 {
 *   "status": true,
 *   "data": [
 *     {
 *       "id": 14,
 *       "productId": 4664496382,
 *       "mandateId": 2010,
 *       "productName": "ajo-ddd",
 *       "paymentAmount": 200,
 *       "serviceReference": "17240719224664496382",
 *       "accountNumber": "2075579177",
 *       "bankCode": "000004",
 *       "accountName": "Ogedengbe sunday olaoluwa",
 *       "phoneNumber": "2348130288477",
 *       "homeAddress": "1 adisa street",
 *       "fileName": null,
 *       "description": "Good good",
 *       "fileBase64String": null,
 *       "fileExtension": null,
 *       "startDate": "17-08-2024",
 *       "endDate": "20-07-2025",
 *       "paymentFrequency": "Monthly",
 *       "packageId": null,
 *       "referenceCode": "3m2PcV6B2X",
 *       "collectionAccountNumber": "2023011710505922352126447149592515",
 *       "mandateType": "Instant",
 *       "remarks": "This is my first mandate",
 *       "email": "sunday4oged@yahoo.com",
 *       "routingOption": "Default",
 *       "created_at": "2024-08-19 13:52:02",
 *       "updated_at": "2024-08-19 13:52:04",
 *       "user_id": 1,
 *       "ajo_id": 3
 *     }
 *   ]
 * }
 *
 * @response 404 {
 *   "status": false,
 *   "message": "No mandates found for this user."
 * }
 *
 * @response 500 {
 *   "status": false,
 *   "message": "An error occurred while retrieving mandates."
 * }
 *
 * @param int $ajoId The ID of the user.
 * @return JsonResponse Returns a JSON response with all mandates or an error message.
 */

     
    public function getAllMandatesByUser(): JsonResponse
    {
        try {
            $userId=Auth::user()->id;
            
            // Retrieve all mandates associated with the user
             $mandates = DirectDebitMandate::where('user_id', $userId)->get();

            if ($mandates->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No mandates found for this user.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $mandates,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while retrieving mandates.',
            ], 500);
        }
    }





    /**
 * Update an existing direct debit mandate.
 * @group Direct Debits Management
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
 * @group Direct Debits Management
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
 * @group Direct Debits Management
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


/**
 * Initiate a direct debit request.
 * @group Direct Debits Management
 * This endpoint initiates a direct debit request with the provided details.
 * @bodyParam recipient string required The email address of the recipient. Example: "example@example.com"
 * @bodyParam expiryDate string required The expiry date of the request in YYYY-MM-DD format. Example: "2024-12-31"
 * @bodyParam startDate string required The start date of the request in YYYY-MM-DD format. Example: "2024-01-01"
 * @bodyParam endDate string required The end date of the request in YYYY-MM-DD format. Example: "2024-12-31"
 * @bodyParam amountLimit number required The maximum amount limit for the direct debit. Example: 1000.00
 * @bodyParam billingCycle string required The billing cycle for the direct debit. Example: "Monthly"
 * @bodyParam description string required A description for the direct debit. Example: "Payment for subscription"
 * @bodyParam productId integer required The ID of the product. Example: 123
 * @bodyParam serviceReference string required A reference for the service. Example: "SR123456"
 * @bodyParam packageId integer required The ID of the package. Example: 456
 * @bodyParam bankCode string required The bank code. Example: "ABC123"
 * @bodyParam mandateAccountName string required The name of the mandate account holder. Example: "John Doe"
 * @bodyParam phoneNumber string required The phone number of the account holder. Example: "+1234567890"
 * @bodyParam mandateAccountNumber string required The account number of the mandate account. Example: "1234567890"
 * @bodyParam payerAddress string required The address of the payer. Example: "123 Main St, Anytown"
 * @bodyParam creditAccountNumber string required The credit account number. Example: "0987654321"
 * @bodyParam creditAccountName string required The name of the credit account holder. Example: "Jane Doe"
 * @bodyParam preferredCompletionOptions string required Preferred completion options for the request. Example: "ASAP"
 * @bodyParam payerName string required The name of the payer. Example: "John Doe"
 *
 * @response 200 {
 *   "data": {
 *     "status": true,
 *     "message": "Request initiated successfully",
 *     "details": { ... }
 *   }
 * }
 *
 * @response 400 {
 *   "message": "Validation error: Details of the errors"
 * }
 *
 * @response 500 {
 *   "message": "Failed to initiate direct debit request. Please try again later."
 * }
 *
 * @post /initiate-direct-debit-request
 */

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





/**
     * Retrieve a list of mandates with pagination.
     * @group Direct Debits Management
     * This endpoint allows user to retrieve a list of mandates based on
     * specific filters such as date range, status, product ID, and bank code.
     * The results are paginated according to the provided page and pageSize parameters.
     *
     * @endpoint GET /api/listMandates
     *
     * @bodyParam startDate string required The start date of the filter range in ISO 8601 format. Example: 2024-08-01T00:00:00.000Z
     * @bodyParam endDate string required The end date of the filter range in ISO 8601 format. Example: 2024-08-31T23:59:59.999Z
     * @bodyParam status string required The status of the mandates to filter. Example: 'active'
     * @bodyParam productId string required The ID of the product to filter mandates. Example: 1
     * @bodyParam bankCode string required The bank code to filter mandates. Example: 'XYZBANK'
     * @bodyParam page int required The page number for pagination. Example: 1
     * @bodyParam pageSize int required The number of items per page. Example: 10
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "mandates": [
     *       {
     *         "id": 1,
     *         "mandateId": 123,
     *         "status": "Active",
     *         "productId": 1,
     *         "bankCode": "XYZBANK",
     *         "created_at": "2024-08-01T12:00:00.000000Z",
     *         "updated_at": "2024-08-02T12:00:00.000000Z"
     *       }
     *     ],
     *     "currentPage": 1,
     *     "totalPages": 1,
     *     "totalItems": 1
     *   }
     * }
     *
     * @response 400 {
     *   "status": false,
     *   "message": "Invalid request parameters.",
     *   "code": 400
     * }
     *
     * @response 401 {
     *   "status": false,
     *   "message": "Unauthenticated.",
     *   "code": 401
     * }
     *
     * @response 500 {
     *   "status": false,
     *   "message": "An error occurred while retrieving mandates.",
     *   "code": 500
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */



    public function listMandates(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'startDate' => 'required|date_format:Y-m-d\TH:i:s.v\Z',
            'endDate' => 'required|date_format:Y-m-d\TH:i:s.v\Z',
            'status' => 'required|string',
            'productId' => 'required|integer',
            'bankCode' => 'required|string',
            'page' => 'required|integer',
            'pageSize' => 'required|integer',
        ]);

        // Call the service to handle the business logic
        $result = $this->directDebitService->listMandates($validatedData);

        // Return the appropriate response based on the service result
        if ($result['status']) {
            return response()->json($result['data'], 200);
        } else {
            return response()->json(['message' => $result['message']], $result['code']);
        }
    }


  
   /**
 * Retrieve the status of a mandate for the specified user.
 * @group Direct Debits Management
 * This endpoint returns the status of a mandate identified by `mandateId` and associated with `ajoId`.
 * The mandate must belong to the user identified by `ajoId`.
 *
 * @endpoint GET /api/getMandateStatus/{mandateId}/{ajoId}
 *
 * @urlParam mandateId int required The ID of the mandate to retrieve. Example: 123
 * @urlParam ajoId int required The ID of the associated user (ajo). Example: 456
 *
 * @response 200 {
 *   "status": true,
 *   "data": {
 *     "mandateId": 123,
 *     "status": "Active",
 *     "updated_at": "2024-08-24T12:00:00.000000Z"
 *   }
 * }
 *
 * @response 400 {
 *   "status": false,
 *   "message": "Mandate does not belong to the authorized user.",
 *   "code": 400
 * }
 *
 * @response 401 {
 *   "status": false,
 *   "message": "Unauthenticated.",
 *   "code": 401
 * }
 *
 * @response 500 {
 *   "status": false,
 *   "message": "An error occurred while retrieving mandate status.",
 *   "code": 500
 * }
 *
 * @param int $mandateId The ID of the mandate.
 * @param int $ajoId The ID of the user (ajo).
 * @return \Illuminate\Http\JsonResponse
 */


     public function getMandateStatus($mandateId,$ajoId): \Illuminate\Http\JsonResponse
    {
        // Call the service to handle the business logic
        $result = $this->directDebitService->getMandateStatus($mandateId,$ajoId);

        // Return the appropriate response based on the service result
        if ($result['status']) {
            return response()->json($result['data'], 200);
        } else {
            return response()->json(['message' => $result['message']], $result['code']);
        }
    }

   /**
     * Retrieve mandate schedules for the authenticated user.
     * @group Direct Debits Management
     * This endpoint returns the schedules of a mandate identified by mandateId
     * and referenceCode for the authenticated user. The response includes pagination
     * information based on the provided page and pageSize parameters.
     *
     * @endpoint POST /api/getMandateSchedules
     *
     * @bodyParam mandateId int required The ID of the mandate. Example: 123
     * @bodyParam referenceCode string required The reference code of the mandate. Example: "ref_code"
     * @bodyParam page int required The page number for pagination. Example: 1
     * @bodyParam pageSize int required The number of items per page. Example: 10
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "schedules": [
     *       {
     *         "scheduleId": 1,
     *         "mandateId": 123,
     *         "amount": "1000",
     *         "status": "Scheduled",
     *         "scheduledDate": "2024-08-24"
     *       }
     *     ],
     *     "currentPage": 1,
     *     "totalPages": 1,
     *     "totalItems": 1
     *   }
     * }
     *
     * @response 400 {
     *   "status": false,
     *   "message": "Mandate does not belong to the authorized user.",
     *   "code": 400
     * }
     *
     * @response 401 {
     *   "status": false,
     *   "message": "Unauthenticated.",
     *   "code": 401
     * }
     *
     * @response 500 {
     *   "status": false,
     *   "message": "An error occurred while retrieving mandate schedules.",
     *   "code": 500
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
     
    public function getMandateSchedules(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'mandateId' => 'required|integer',
            'referenceCode' => 'required|string',
            'page' => 'required|integer',
            'pageSize' => 'required|integer',
        ]);

        return response()->json($this->getMandateSchedules($validatedData));
    }


 /**
     * Get paginated products created by the authenticated user.
     * @group Direct Debits Management
     * This endpoint returns a paginated list of products that were created by the
     * authenticated user, ordered by creation date in descending order.
     *
     * @endpoint GET /api/getUserProducts
     *
     * @queryParam per_page int The number of items to return per page. Default is 15. Example: 20
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "productName": "Product Name",
     *       "isPacketBased": false,
     *       "isUserResponsibleForCharges": false,
     *       "partialCollectionEnabled": false,
     *       "collectionAccountId": "Account ID",
     *       "productDescription": "Product Description",
     *       "classification": "SubscriptionService",
     *       "remarks": "Remarks",
     *       "feeType": "FixedAmount",
     *       "created_at": "2024-08-24T12:00:00.000000Z",
     *       "updated_at": "2024-08-24T12:00:00.000000Z",
     *       "user_id": "12345"
     *     }
     *   ],
     *   "first_page_url": "http://example.com/api/user/products?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/api/user/products?page=1",
     *   "next_page_url": null,
     *   "path": "http://example.com/api/user/products",
     *   "per_page": 15,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserProducts(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $products = $this->directDebitService->getUserProducts($perPage);
        return response()->json($products);
    }
    
    
     /**
     * Get paginated transactions created by the authenticated user.
     * @group Direct Debits Management
     * This endpoint returns a paginated list of transactions created by the
     * authenticated user, ordered by creation date in descending order.
     *
     * @endpoint GET /api/getAllMandatesPerUser
     *
     * @queryParam per_page int The number of items to return per page. Default is 15. Example: 20
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "productId": 123,
     *       "mandateId": "mandate_123",
     *       "productName": "Product Name",
     *       "paymentAmount": "1000",
     *       "serviceReference": "service_ref",
     *       "accountNumber": "1234567890",
     *       "bankCode": "XYZ",
     *       "accountName": "John Doe",
     *       "phoneNumber": "1234567890",
     *       "homeAddress": "123 Main St",
     *       "fileName": "document.pdf",
     *       "description": "Description",
     *       "fileBase64String": "base64string",
     *       "fileExtension": "pdf",
     *       "startDate": "2024-01-01",
     *       "endDate": "2024-12-31",
     *       "paymentFrequency": "Monthly",
     *       "packageId": 456,
     *       "referenceCode": "ref_code",
     *       "collectionAccountNumber": "9876543210",
     *       "mandateType": "type",
     *       "remarks": "Remarks",
     *       "email": "example@example.com",
     *       "routingOption": "option",
     *       "created_at": "2024-08-24T12:00:00.000000Z",
     *       "updated_at": "2024-08-24T12:00:00.000000Z",
     *       "user_id": "12345"
     *     }
     *   ],
     *   "first_page_url": "http://example.com/api/user/transactions?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/api/user/transactions?page=1",
     *   "next_page_url": null,
     *   "path": "http://example.com/api/user/transactions",
     *   "per_page": 15,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllMandatesPerUser(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $transactions = $this->directDebitService->getUserTransactions($perPage);
        return response()->json($transactions);
    }































}
