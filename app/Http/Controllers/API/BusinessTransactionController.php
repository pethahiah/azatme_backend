<?php

namespace App\Http\Controllers\API;


use App\Charge;
use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ChargeService;
use App\Services\MposService;
use App\Services\Referrals;
use Illuminate\Http\Request;
use App\Http\Requests\ExpenseRequest;
use App\Product;
use App\Customer;
use App\Business;
use App\BusinessTransaction;
use App\BusinessWithdrawal;
use App\Invoice;
use App\User;
use Auth;
use App\Bank;
use Mail;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\BusinessNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use App\Mail\BusinessPaylinkMail;
use App\Setting;
use App\Active;
use Illuminate\Support\Facades\Log;
use App\Services\PaythruService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Mail\ErrorMail;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;




class BusinessTransactionController extends Controller
{
    //

    public $referral;
    public $paythruService;
    public $mPos;
    public $chargeService;

    public function __construct(PaythruService $paythruService, Referrals $referral, ChargeService $chargeService, MposService $mPos)
    {
        $this->paythruService = $paythruService;
        $this->referral = $referral;
        $this->chargeService = $chargeService;
        $this->mPos = $mPos;
    }

    /**
     * @group Business Transactions
     *
     * API endpoints for Business Transactions functionalities.
     */

    /**
     * Create a Product or Multiple Products
     *
     * This method creates one or multiple products based on the request data.
     * It accepts an array of products or a single product's details, creates the product(s),
     * and assigns a common unique code to each product in the array.
     * It ensures the authenticated user is assigned as the owner of the product(s).
     *
     * If `$products` is an array, it creates multiple products with a common unique code.
     * If `$products` is not an array, it creates a single product with its own unique code.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance containing product data.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with the created product(s) or an error message.
     *
     * @bodyParam products array An array of products, where each product is an associative array with keys:
     *  - name string The name of the product.
     *  - description string The description of the product.
     *  - business_code string The business code associated with the product.
     *  - category_id int The ID of the category the product belongs to.
     *  - subcategory_id int The ID of the subcategory the product belongs to.
     *  - amount float The amount of the product.
     *  - quantity int The quantity of the product.
     *
     * @bodyParam name string The name of the product (used when creating a single product).
     * @bodyParam description string The description of the product (used when creating a single product).
     * @bodyParam business_code string The business code associated with the product (used when creating a single product).
     * @bodyParam category_id int The ID of the category the product belongs to (used when creating a single product).
     * @bodyParam subcategory_id int The ID of the subcategory the product belongs to (used when creating a single product).
     * @bodyParam amount float The amount of the product (used when creating a single product).
     * @bodyParam quantity int The quantity of the product (used when creating a single product).
     *
     * @response 200 {
     *   "products": [
     *     {
     *       "id": 1,
     *       "name": "Product Name",
     *       "description": "Product Description",
     *       "unique_code": "randomunique",
     *       "business_code": "business_code",
     *       "category_id": 1,
     *       "subcategory_id": 1,
     *       "amount": 100.00,
     *       "quantity": 10,
     *       "user_id": 1,
     *       "created_at": "2024-08-18T00:00:00Z",
     *       "updated_at": "2024-08-18T00:00:00Z"
     *     }
     *   ]
     * }
     *
     * @response 422 {
     *   "error": "Validation failed for the input data."
     * }
     * @post /create-product
     */

    public function creatProduct(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $products = $request->products;

        // Check if $products is an array
        if (is_array($products)) {
            $createdProducts = [];
            $commonUniqueCode = Str::random(10);

            foreach ($products as $productData) {
                // Add validation here to ensure required keys are present in $productData

                $product = Product::create([
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'unique_code' => $commonUniqueCode, // Use the common unique code
                    'business_code' => $productData['business_code'],
                    'category_id' => $productData['category_id'],
                    'subcategory_id' => $productData['subcategory_id'],
                    'amount' => $productData['amount'],
                    'quantity' => $productData['quantity'],
                    'user_id' => $userId,
                ]);

                $createdProducts[] = $product;
            }

            return response()->json($createdProducts);
        } else {
            // Assuming that the single product data is in the request as separate keys
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'unique_code' => Str::random(10), // Generate a unique code for a single product
                'business_code' => $request->business_code,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'amount' => $request->amount,
                'quantity' => $request->quantity,
                'user_id' => $userId,
            ]);

            return response()->json($product);
        }
}


    /**
     * Get All Products for the Authenticated Merchant
     *
     * This method retrieves all products associated with the currently authenticated merchant.
     * It allows for pagination of the results based on the `per_page` parameter provided in the request.
     * The products are sorted by the most recently created first.
     *
     *
     * @queryParam per_page int The number of products per page. Default is 10.
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Product Name",
     *       "description": "Product Description",
     *       "unique_code": "randomunique",
     *       "business_code": "business_code",
     *       "category_id": 1,
     *       "subcategory_id": 1,
     *       "amount": 100.00,
     *       "quantity": 10,
     *       "user_id": 1,
     *       "created_at": "2024-08-18T00:00:00Z",
     *       "updated_at": "2024-08-18T00:00:00Z"
     *     }
     *   ],
     *   "first_page_url": "http://yourdomain.com/api/products?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://yourdomain.com/api/products?page=1",
     *   "links": [
     *     {
     *       "url": null,
     *       "label": "&laquo; Previous",
     *       "active": false
     *     },
     *     {
     *       "url": "http://yourdomain.com/api/products?page=1",
     *       "label": "1",
     *       "active": true
     *     },
     *     {
     *       "url": null,
     *       "label": "Next &raquo;",
     *       "active": false
     *     }
     *   ],
     *   "next_page_url": null,
     *   "path": "http://yourdomain.com/api/products",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 400 {
     *   "error": "Invalid pagination parameters or other request errors."
     * }
     *
     * @get /all-product
     */
    public function getAllProductsPerBusinessMerchant(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $AuthUser = Auth::user()->id;

    $getAllProductsPerBusinessMerchant = Product::where('user_id', $AuthUser)
        ->latest('created_at')
        ->paginate($perPage);

    return response()->json($getAllProductsPerBusinessMerchant);
}

    /**
     * Get All Products for a Specific Business
     *
     * This method retrieves all products associated with a specific business, identified by its `business_code`.
     * The products are sorted by the most recently created first and are paginated with a fixed number of items per page.
     *
     * @param string $businessCode The unique code identifying the business for which to retrieve products.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing a paginated list of products for the specified business.
     *
     * @queryParam per_page int The number of products per page. Default is 50.
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Product Name",
     *       "description": "Product Description",
     *       "unique_code": "randomunique",
     *       "business_code": "business_code",
     *       "category_id": 1,
     *       "subcategory_id": 1,
     *       "amount": 100.00,
     *       "quantity": 10,
     *       "user_id": 1,
     *       "created_at": "2024-08-18T00:00:00Z",
     *       "updated_at": "2024-08-18T00:00:00Z"
     *     }
     *   ],
     *   "first_page_url": "http://yourdomain.com/api/products?business_code=business_code&page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://yourdomain.com/api/products?business_code=business_code&page=1",
     *   "links": [
     *     {
     *       "url": null,
     *       "label": "&laquo; Previous",
     *       "active": false
     *     },
     *     {
     *       "url": "http://yourdomain.com/api/products?business_code=business_code&page=1",
     *       "label": "1",
     *       "active": true
     *     },
     *     {
     *       "url": null,
     *       "label": "Next &raquo;",
     *       "active": false
     *     }
     *   ],
     *   "next_page_url": null,
     *   "path": "http://yourdomain.com/api/products",
     *   "per_page": 50,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 400 {
     *   "error": "Invalid request or business code not found."
     * }
     *
     * @post /initiate-business-transaction/{business_code}
     */
    public function getProductsPerBusiness($businessCode)
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllProductsPerBusiness = product::where('business_code', $businessCode)->latest()->paginate($pageNumber);
        return response()->json($getAllProductsPerBusiness);
    }

    /**
     * Get All Invoices for a Specific Business
     *
     * This method retrieves all invoices for a specific business, identified by its `business_code`.
     * It filters invoices based on the business owner ID and the provided business code. It excludes entries
     * without an invoice number and sorts the results first by `invoice_number` in descending order and then by `email`.
     * The data is grouped by `product_code` and formatted to include a summary of the total residual amount paid per product code.
     * The formatted data is then paginated.
     *
     * @urlParam businessCode string required The code of the business. Example: "BUS123"
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "Product Code": "product_code_1",
     *       "email": "customer@example.com",
     *       "total_Amount_Paid": 500.00,
     *       "data": [
     *         {
     *           "id": 1,
     *           "invoice_number": "INV12345",
     *           "product_code": "product_code_1",
     *           "email": "customer@example.com",
     *           "residualAmount": 250.00,
     *           "created_at": "2024-08-18T00:00:00Z",
     *           "updated_at": "2024-08-18T00:00:00Z"
     *         },
     *         ...
     *       ]
     *     }
     *   ],
     *   "first_page_url": "http://yourdomain.com/api/invoices?business_code=business_code&page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://yourdomain.com/api/invoices?business_code=business_code&page=1",
     *   "links": [
     *     {
     *       "url": null,
     *       "label": "&laquo; Previous",
     *       "active": false
     *     },
     *     {
     *       "url": "http://yourdomain.com/api/invoices?business_code=business_code&page=1",
     *       "label": "1",
     *       "active": true
     *     },
     *     {
     *       "url": null,
     *       "label": "Next &raquo;",
     *       "active": false
     *     }
     *   ],
     *   "next_page_url": null,
     *   "path": "http://yourdomain.com/api/invoices",
     *   "per_page": 50,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 401 {
     *   "message": "Unauthorized"
     * }
     *
     * @response 500 {
     *   "message": "An error occurred"
     * }
     *
     * @get /get-invoice/{businessCode}
     */

public function getAllInvoiceByBusiness($businessCode)
{
    try {
        $getUser = Auth::user();
        if (!$getUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pageNumber = 50;

        $getAllInvoiceByABusiness = businessTransaction::where('owner_id', $getUser->id)
            ->where('business_code', $businessCode)
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number', 'desc')
            ->orderBy('email')
            ->get();

        // Group the data by product_code
        $groupedData = $getAllInvoiceByABusiness->groupBy('product_code');

        $formattedData = [];
        foreach ($groupedData as $productCode => $entries) {

	$totalResidualAmount = $entries->sum('residualAmount');

            $formattedData[] = [
                'Product Code' => $productCode,
                'email' => $entries->first()['email'],
		'total_Amount_Paid' => $totalResidualAmount,
                'data' => $entries->toArray(),
            ];
        }

        // Paginate the formatted data
        $paginatedData = new LengthAwarePaginator(
            array_slice($formattedData, 0, $pageNumber),
            count($formattedData),
            $pageNumber
        );

        return response()->json($paginatedData);
    } catch (\Exception $e) {
        return response()->json(['message' => 'An error occurred'], 500);
    }
}

    /**
     * Get All Links for a Specific Business
     *
     * This method retrieves all transactions for a specific business identified by its `business_code` where no invoice number is present.
     * It filters transactions based on the business owner ID and the provided business code. The results are sorted by `created_at` in descending order and then by `email`.
     * The data is grouped by `product_code` and formatted to include a summary of the total residual amount paid per product code.
     * The formatted data is then paginated.
     *
     * @urlParam businessCode string required The code of the business. Example: "BUS123"
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "Product Code": "product_code_1",
     *       "email": "customer@example.com",
     *       "total_Amount_Paid": 500.00,
     *       "data": [
     *         {
     *           "id": 1,
     *           "invoice_number": null,
     *           "product_code": "product_code_1",
     *           "email": "customer@example.com",
     *           "residualAmount": 250.00,
     *           "created_at": "2024-08-18T00:00:00Z",
     *           "updated_at": "2024-08-18T00:00:00Z"
     *         },
     *         ...
     *       ]
     *     }
     *   ],
     *   "first_page_url": "http://yourdomain.com/api/links?business_code=business_code&page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://yourdomain.com/api/links?business_code=business_code&page=1",
     *   "links": [
     *     {
     *       "url": null,
     *       "label": "&laquo; Previous",
     *       "active": false
     *     },
     *     {
     *       "url": "http://yourdomain.com/api/links?business_code=business_code&page=1",
     *       "label": "1",
     *       "active": true
     *     },
     *     {
     *       "url": null,
     *       "label": "Next &raquo;",
     *       "active": false
     *     }
     *   ],
     *   "next_page_url": null,
     *   "path": "http://yourdomain.com/api/links",
     *   "per_page": 50,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 401 {
     *   "message": "Unauthorized"
     * }
     *
     * @response 500 {
     *   "message": "An error occurred"
     * }
     *
     * @get /get-link/{businessCode}
     */

public function getAllIinkByBusiness($businessCode)
    {
        try {
            $getUser = Auth::user();
            if (!$getUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            $pageNumber = 50;
            $getAllIinkByBusiness = businessTransaction::where('owner_id', $getUser->id)
                 ->where('business_code', $businessCode)
                ->whereNull('invoice_number')
                ->orderBy('created_at', 'desc')
                ->orderBy('email')
                ->get();

	$groupedData = $getAllIinkByBusiness->groupBy('product_code');

	$formattedData = [];
        foreach ($groupedData as $productCode => $entries) {

        $totalResidualAmount = $entries->sum('residualAmount');

            $formattedData[] = [
                'Product Code' => $productCode,
                'email' => $entries->first()['email'],
                'total_Amount_Paid' => $totalResidualAmount,
                'data' => $entries->toArray(),
            ];
        }
        $paginatedData = new LengthAwarePaginator(
            array_slice($formattedData, 0, $pageNumber),
            count($formattedData),
            $pageNumber
        );

        return response()->json($paginatedData);
    } catch (\Exception $e) {
        return response()->json(['message' => 'An error occurred'], 500);
    }
}



private function generateDynamicQrCode(Request $request, $merchantNumber, $totalAmount, $invoice_number, $now, $product, $vat)
{
  $testUrl = "https://services.paythru.ng";
//  $token = $this->paythruService->handle();
 $token = $this->paythruService->handle();

     if (!$token) {
        return "Token retrieval failed";
    }
  $endpoint = $testUrl.'/Nqr/agg/merchant/transaction';
$data = [
  "channel" => $product,
  "subMchNo" => $request->sunMchNo,
  "codeType" => $now,
  "amount" => $totalAmount,
  "order_no" => $invoice_number,
  "orderType" => $vat,

];
$response = Http::withHeaders([
'Content-Type' => 'application/json',
'Authorization' => $token,
])->post($endpoint."/$merchantNumber", $data);

if($response->failed())
{
return false;
}
$ngrGenerateDynamicCode = json_decode($response->body(), true);
$returnCode = $ngrGenerateDynamicCode->response->returnCode;
// Generate the NQR code
QrCode::size(300)->generate($returnCode);
return $returnCode;
}


 private function generateUniqueCode()
    {
        return Str::random(10);
    }


    private function paymentData($totalAmount, $product)
    {
        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);
        $secret = env('PayThru_App_Secret');
        $PayThru_productId = env('PayThru_business_productid');
        $prodUrl = env('PayThru_Base_Live_Url');
        $hash = hash('sha512', $timestamp  . $secret);
        $hashSign = hash('sha512', $totalAmount . $secret);
        $data = [
            'amount' => $totalAmount,
            'productId' => $PayThru_productId,
            'transactionReference' => time() . $product->id,
            'paymentDescription' => $product->description,
            'paymentType' => 1,
            'sign' => $hashSign,
            'displaySummary' => false,
        ];

        return $data;
    }

    /**
     * Start a business transaction and generate payment link or invoice based on `moto_id`.
     *
     * This endpoint handles business transactions by calculating the total amount, VAT, and generating
     * a payment link or invoice based on the provided `moto_id`.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing input data.
     * @param string $business_code The unique code identifying the business.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing either the payment transaction data or invoice URL.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException If token retrieval fails or other errors occur.
     *
     * @bodyParam email string The email address of the customer. Example: "customer@example.com"
     * @bodyParam unique_code array|string The unique codes of the products being purchased. Example: ["ABC123", "XYZ789"]
     * @bodyParam quantity array|int The quantities of the products being purchased. Example: [1, 2]
     * @bodyParam moto_id int The mode of transaction: 1 for payment link, 2 for invoice. Example: 1
     * @bodyParam bankName string The name of the bank. Example: "Bank of Example"
     * @bodyParam bankCode string The bank code. Example: "123456"
     * @bodyParam account_number string The bank account number. Example: "000123456789"
     * @bodyParam due_days int The number of days for the invoice due date. Only used if moto_id is 2. Example: 30
     *
     * @response 200 {
     *     "status": "Successful",
     *     "transactions": {
     *         "transaction_id": "txn_123456",
     *         "payLink": "https://example.com/paylink"
     *     }
     * }
     *
     * @response 400 {
     *     "error": "Token retrieval failed"
     * }
     *
     * @response 403 {
     *     "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @response 500 {
     *     "error": "Your request is unsuccessful at this time, Please try again"
     * }
     *
     * @post /initiate-business-transaction/{business_code}
     */

public function startBusinessTransaction(Request $request, $business_code)
    {
        ini_set('max_execution_time', 300);
        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);
        $secret = env('PayThru_App_Secret');
        $ath = env('token');
        $PayThru_productId = env('PayThru_business_productid');
        $prodUrl = env('PayThru_Base_Live_Url');

      //  return $token = $this->paythruService->handle();

        $idCode = $this->generateUniqueCode();

        $uniqueCodes = $request->input('unique_code');
        $quantities = $request->input('quantity');

        // If "quantity" is not an array, convert it to an array
    if (!is_array($quantities)) {
        $quantities = [$quantities];
    }
       //  return $quantities;
        $input['email'] = $request->input('email');
        $em = $input['email'];
        $user = Customer::where('customer_email', $em)->first();
        $user->customer_email;
	$cusName = $user->customer_name;
        $vat = 0;

        $getBusinessVatOption = Business::where('owner_id', Auth::user()->id)
            ->where('business_code', $business_code)
            ->first();
	$busName = $getBusinessVatOption->business_name;

            if (count($uniqueCodes) === 1 && count($quantities) === 1) {
                $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;
                $product = Product::where('unique_code', $uniqueCodes)->first();
                $quantity = is_numeric($quantities[0]) ? $quantities[0] : 0;
                $amount = is_numeric($product->amount) ? $product->amount : 0;

                $vatAmount = $amount * $quantity * $vat;
                $grandTotal = ($amount * $quantity) + $vatAmount;
                $totalAmount = $grandTotal;

                if ($request['moto_id'] == 1) {
                    $token = $this->paythruService->handle();
                    if (!$token) {
                        return "Token retrieval failed";
                    }

                    $data = $this->paymentData($totalAmount, $product);
                    $url = $prodUrl;
                    $urls = $url . '/transaction/create';


                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                    ])->post($urls, $data);

                    if ($response->failed()) {
                        return response()->json(['error' => 'Your request is unsucceefull at this time,Please try again'], 500);
                    } else {
                        $transaction = json_decode($response->body(), true);
                        $paylink = $transaction['payLink'];
			  Mail::to($user->customer_email, $busName, $cusName)->send(new BusinessPaylinkMail($paylink, $busName, $cusName));
                        //Mail::to($user->customer_email)->send(new BusinessPaylinkMail($paylink));
                        if ($paylink) {
                            $getLastString = explode('/', $paylink);
                            $now = end($getLastString);

                            $info = BusinessTransaction::create([
                                'owner_id' => Auth::user()->id,
                                'name' => $product->name,
                                'unique_code' => $product->unique_code,
                                'product_id' => $product->id,
                                'email' => $user->customer_email,
                                'transaction_amount' => $product->amount,
                                'business_code' => $product->business_code,
                                'description' => $product->description,
                                'moto_id' => $request['moto_id'],
                                'bankName' => $request['bankName'],
                                'bankCode' => $request['bankCode'],
                                'account_number' => $request['account_number'],
                                'qty' => $quantity,
                                'vat' => $vat,
				                'Grand_total' => $grandTotal,
                                'paymentReference' => $now,
                                'product_code' => $this->generateUniqueCode()
                            ]);
                            return response()->json($transaction);
                        }
                    }
                } elseif ($request['moto_id'] == 2) {
                    $quantity = is_numeric($quantities[0]) ? $quantities[0] : 0;
                   // return $quantity;
                    $amount = is_numeric($product->amount) ? $product->amount : 0;

                    $vatAmount = $amount * $quantity * $vat;
                    $grandTotal = ($amount * $quantity) + $vatAmount;
                    $totalAmount = $grandTotal;


                    $latest = BusinessTransaction::latest()->first();
                    $invoice_number = "";
                   if (empty($latest)) {
    			$invoice_number = 'azatme_invoice';
				} else {
    			$string = random_int(0, 999999);
    			$string = str_pad($string, 9, '2', STR_PAD_LEFT);
    			$invoice_number = 'azatme__invoice' . ($string + 1);
		}
                    $token = $this->paythruService->handle();
                    if (!$token) {
                        return "Token retrieval failed";
                    } elseif (is_string($token) && strpos($token, '403') !== false) {
                        return response()->json(['error' => 'Access denied. You do not have permission to access this resource.'], 403);
                    }

                    $data = $this->paymentData($totalAmount, $product);
                    $url = $prodUrl;
                    $urls = $url . '/transaction/create';

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                    ])->post($urls, $data);
                    if ($response->failed()) {
                        return response()->json(['error' => 'Your request is unsucceefull at this time,Please try again'], 500);
                    } else {
                        $transaction = json_decode($response->body(), true);
                        $paylink = $transaction['payLink'];
                        $getLastString = (explode('/', $paylink));
                        $now = end($getLastString);
                        $current = \Carbon\Carbon::now();
                        $invoice = BusinessTransaction::create([
                            'owner_id' => Auth::id(),
                            'name' => $product->name,
                            'unique_code' => $product->unique_code,
                            'email' => $user->customer_email,
                            'product_id' => $product->id,
                            'account_number' => $request->account_number,
                            'description' => $product['description'],
                            'transaction_amount' => $product->amount,
                            'business_code' => $product->business_code,
                            'bankName' => $request['bankName'],
                            'bankCode' => $request['bankCode'],
                            'moto_id' => $request['moto_id'],
                            'invoice_number' => $invoice_number,
                            'qty' => $quantity,
                            'vat' => $vatAmount,
                            'Grand_total' => $grandTotal,
                            'due_days' => $request->due_days,
			    'due_date' => $current->addDays($request->due_days),
                           // 'due_date' => $current->addDays($request->due_days),
                            'issue_date' => \Carbon\Carbon::now(),
                            'paymentReference' => $now,
                            'product_code' => $this->generateUniqueCode()
                        ]);


                        $getBusiness = User::where('id', Auth::user()->id)->first();
                        $business = Business::where('owner_id', Auth::user()->id)->first();
                        $InvoiceTran = BusinessTransaction::where('product_code', $invoice->product_code)->first();
                        $sum = $InvoiceTran->vat;
                        $sum1 = $InvoiceTran->transaction_amount;
                        $cusInvoEmail = $InvoiceTran->email;
                        $getUserInvo = Customer::where('customer_email', $cusInvoEmail)->first();
                        //$word = $this->numberToWord($totalAmount);

                       $pdf = PDF::loadView('generate/invo', compact('invoice', 'getBusiness', 'getUserInvo', 'paylink', 'business'));

                        $filename = 'invoice_' . '_' . time() . '.pdf';

                        \Storage::disk('public')->put($filename, $pdf->output());

                        $pdf_url = \Storage::disk('public')->url($filename);

                       $pdf = PDF::loadView('generate/invo', compact('invoice', 'getBusiness', 'getUserInvo', 'paylink', 'business'));

                        $filename = 'invoice_' . '_' . time() . '.pdf';

                        \Storage::disk('public')->put($filename, $pdf->output());

                        $pdf_url = \Storage::disk('public')->url($filename);


                        return response()->json([
                            'status' => 'Successful',
                            'link' => $pdf_url
                        ]);
                    }
                }
            } elseif (count($uniqueCodes) === count($quantities) && count($uniqueCodes) > 1) {
            $totalAmount = 0;
            $totalVatAmount = 0;
            $totalQuantity = 0;

            $idCode = $this->generateUniqueCode();


            foreach ($uniqueCodes as $index => $uniqueCode) {
                $quantity = $quantities[$index];
                //return $quantity;
                $product = Product::where('unique_code', $uniqueCode)->first();

                if ($product) {
                    $vat = ($getBusinessVatOption->vat_option == 'yes') ? 0.075 : 0;

                    $amount = is_numeric($product->amount) ? $product->amount : 0;

                    // Calculate VAT and grand total for the product
                    $vatAmount = $amount * $quantity * $vat;
                    $grandTotal = ($amount * $quantity) + $vatAmount;


                    $totalAmount += $grandTotal;
                    $totalVatAmount += $vatAmount;
                    $totalQuantity += $quantity;
               }




                if ($request['moto_id'] == 1) {


                    $token = $this->paythruService->handle();
                    $data = $this->paymentData($totalAmount, $product);
                    $url = $prodUrl;
                    $urls = $url . '/transaction/create';


                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                    ])->post($urls, $data);
                    if ($response->failed()) {
                        return false;
                    } else {
                        $transaction = json_decode($response->body(), true);

                        if(!$transaction['successful'])
                 {

                    // return "Whoops! ". json_encode($transaction);
	                    return response()->json(['message' => 'Whoops! ' . json_encode($transaction['message'])], 400);
                }
                       // <p>NQR Code</p>
  //  <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(300)->generate($returnCode)) }}" alt="NQR Code">
                        $paylink = $transaction['payLink'];
//			echo $user->customer_email;
                       // Mail::to($user->customer_email)->send(new BusinessPaylinkMail($paylink));
                        if ($paylink) {
                            $getLastString = explode('/', $paylink);
                            $now = end($getLastString);
                            $info = BusinessTransaction::create([
                                'owner_id' => Auth::user()->id,
                                'name' => $product->name,
                                'unique_code' => $product->unique_code,
                                'product_id' => $product->id,
                                'email' => $user->customer_email,
                                'transaction_amount' => $product->amount,
                                'business_code' => $product->business_code,
                                'description' => $product->description,
                                'moto_id' => $request['moto_id'],
                                'bankName' => $request['bankName'],
                                'bankCode' => $request['bankCode'],
                                'account_number' => $request['account_number'],
                                'qty' => $quantity,
                                'vat' => $vatAmount,
                                'Grand_total' => $grandTotal,
                                'paymentReference' => $now,
                                'product_code' => $idCode
                            ]);
                        }


			 $InvoiceTran = businessTransaction::where('product_code', $info->product_code)->get();

                    }
                } elseif ($request['moto_id'] == 2) {

                $latest = BusinessTransaction::latest()->first();
                $merchantNumber = $request->merchantNumber;

                $invoice_number = "";
if (empty($latest)) {
    $invoice_number = 'azatme_invoice';
} else {
    $string = random_int(0, 999999);
    $string = str_pad($string, 9, '2', STR_PAD_LEFT);
    $invoice_number = 'azatme__invoice' . ($string + 1);
}

//return $invoice_number;


               $token = $this->paythruService->handle();
                   if (!$token) {
                    return response()->json([
                        'error' => 'Token retrieval failed',
                    ], 500);
                }
                // return $token;
                    $data = $this->paymentData($totalAmount, $product);
                    $url = $prodUrl;
                    $urls = $url . '/transaction/create';

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                    ])->post($urls, $data);
                    //return $response;
                  //  $response->throw();
                    if ($response->failed()) {
                        return false;
                    } else {
                        $transaction = json_decode($response->body(), true);

                       $transaction;
                        $paylink = $transaction['payLink'];
                        $getLastString = (explode('/', $paylink));
                        $now = end($getLastString);
                        //return $now;
                        $current = \Carbon\Carbon::now();
                        $invoice = BusinessTransaction::create([
                            'owner_id' => Auth::id(),
                            'name' => $product->name,
                            'unique_code' => $product->unique_code,
                            'email' => $user->customer_email,
                            'product_id' => $product->id,
                            'account_number' => $request->account_number,
                            'description' => $product['description'],
                            'transaction_amount' => $product->amount,
                            'business_code' => $product->business_code,
                            'bankName' => $request['bankName'],
                            'bankCode' => $request['bankCode'],
                            'moto_id' => $request['moto_id'],
                            'invoice_number' => $invoice_number,
                            'qty' => $quantity,
                            'vat' => $vatAmount,
                            'Grand_total' => $grandTotal,
                            'due_days' => $request->due_days,
			                'due_date' => $current->addDays($request->due_days),
                            //'due_date' => $current->addDays($request->due_days),
                            'issue_date' => \Carbon\Carbon::now(),
                            'paymentReference' => $now,
                            'product_code' => $idCode
                        ]);

                    }
                  //  $nqr = $this->generateDynamicQrCode($request, $totalAmount, $invoice_number, $vat, $product, $now, $merchantNumber);

                }

            }

        if ($request['moto_id'] == 1) {
	   // Mail::to($user->customer_email)->send(new BusinessPaylinkMail($paylink));
	      Mail::to($user->customer_email, $busName, $cusName)->send(new BusinessPaylinkMail($paylink, $busName, $cusName));
            $transactionResponse = [
                'status' => 'Successful',
                'transactions' => $transaction
            ];
            return response()->json($transactionResponse);
        }

        elseif ($request['moto_id'] == 2) {
            $getBusiness = User::where('id', Auth::user()->id)->first();
            $business = Business::where('owner_id', Auth::user()->id)->first();
            $InvoiceTran = businessTransaction::where('product_code', $idCode)->get();
            $invoiceInfo = $InvoiceTran[0];
            $word = $this->numberToWord($totalAmount);

            $cusInvoEmail = $InvoiceTran[0]->email;
            $getUserInvo = Customer::where('customer_email', $cusInvoEmail)->first();

            $pdf = PDF::loadView('generate/invoice', compact('InvoiceTran', 'invoiceInfo','getBusiness', 'business', 'getUserInvo', 'paylink', 'word'));

            $filename = 'invoice_' . '_' . time() . '.pdf';

            // Save the PDF to your server's storage directory
            \Storage::disk('public')->put($filename, $pdf->output());

            // Get the public URL of the saved PDF
            $pdf_url = \Storage::disk('public')->url($filename);



        return response()->json([
        'status' => 'Successful',
        'link' => $pdf_url
            ]);
        }
    }
    }


private function convertIntegerToWords($num, $list1, $list2, $list3)
    {
        $num = (string)((int)$num);

        if ((int)($num) && ctype_digit($num)) {
            $words = array();

            $num = str_replace(array(',', ' '), '', trim($num));

            $num_length = strlen($num);
            $levels = (int)(($num_length + 2) / 3);
            $max_length = $levels * 3;
            $num = substr('00' . $num, -$max_length);
            $num_levels = str_split($num, 3);

            foreach ($num_levels as $num_part) {
                $levels--;
                $hundreds = (int)($num_part / 100);
                $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ($hundreds == 1 ? '' : 's') . ' and' : '');
                $tens = (int)($num_part % 100);
                $singles = '';

                if ($tens < 20) {
                    $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
                } else {
                    $tens = (int)($tens / 10);
                    $tens = ' ' . $list2[$tens] . ' ';
                    $singles = (int)($num_part % 10);
                    $singles = ' ' . $list1[$singles] . ' ';
                }
                $words[] = $hundreds . $tens . $singles . (($levels && (int)($num_part)) ? ' ' . $list3[$levels] . ' ' : '');
            }
            $commas = count($words);
            if ($commas > 1) {
                $commas = $commas - 1;
            }

            $words = implode(', ', $words);

            $words = trim(str_replace(' ,', ',', ucwords($words)), ', ');

            return $words;
        } elseif (!((int)$num)) {
            return 'Zero';
        }
        return '';
    }

    private function convertDecimalToWords($num, $list2, $list1)
    {
        $num = ltrim($num, '0'); // Remove leading zeros

        // Convert the decimal part to words
        $decimalWords = '';

        if (!empty($num)) {
            $decimalWords = '';

            if (strlen($num) == 1) {
                // Handle single-digit decimals
                $digit = (int)$num[0];
                $decimalWords .= $list1[$digit];
            } elseif (strlen($num) == 2) {
                // Handle two-digit decimals
                $tensDigit = (int)$num[0];
                $onesDigit = (int)$num[1];

                if ($tensDigit == 0) {
                    $decimalWords .= $list1[$onesDigit];
                } elseif ($tensDigit == 1) {
                    $decimalWords .= $list1[10 + $onesDigit];
                } else {
                    $decimalWords .= $list2[$tensDigit];
                    if ($onesDigit > 0) {
                        $decimalWords .= ' ' . $list1[$onesDigit];
                    }
                }
            }
        }
        return $decimalWords;
    }


 private function numberToWord($num = '')
    {
        $num    = (string) ((int) $num);

        if ((int) ($num) && ctype_digit($num)) {
            $words  = array();

            $num    = str_replace(array(',', ' '), '', trim($num));

            $list1  = array(
                '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven',
                'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen',
                'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
            );

            $list2  = array(
                '', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty',
                'seventy', 'eighty', 'ninety', 'hundred'
            );

            $list3  = array(
                '', 'thousand', 'million', 'billion', 'trillion',
                'quadrillion', 'quintillion', 'sextillion', 'septillion',
                'octillion', 'nonillion', 'decillion', 'undecillion',
                'duodecillion', 'tredecillion', 'quattuordecillion',
                'quindecillion', 'sexdecillion', 'septendecillion',
                'octodecillion', 'novemdecillion', 'vigintillion'
            );

            $num_length = strlen($num);
            $levels = (int) (($num_length + 2) / 3);
            $max_length = $levels * 3;
            $num    = substr('00' . $num, -$max_length);
            $num_levels = str_split($num, 3);

            foreach ($num_levels as $num_part) {
                $levels--;
                $hundreds   = (int) ($num_part / 100);
                $hundreds   = ($hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ($hundreds == 1 ? '' : 's') . 'and' : '');
                $tens       = (int) ($num_part % 100);
                $singles    = '';

                if ($tens < 20) {
                    $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
                } else {
                    $tens = (int) ($tens / 10);
                    $tens = ' ' . $list2[$tens] . ' ';
                    $singles = (int) ($num_part % 10);
                    $singles = ' ' . $list1[$singles] . ' ';
                }
                $words[] = $hundreds . $tens . $singles . (($levels && (int) ($num_part)) ? ' ' . $list3[$levels] . ' ' : '');
            }
            $commas = count($words);
            if ($commas > 1) {
                $commas = $commas - 1;
            }

            $words  = implode(', ', $words);

            $words  = trim(str_replace(' ,', ',', ucwords($words)), ', ');
//            if ($commas) {
  //              $words  = str_replace(',', ' and', $words);
    //        }

            return $words;
        } else if (!((int) $num)) {
            return 'Zero';
        }
        return '';
    }

  //Calling PayThru gateway for transaction response updates
 public function webhookBusinessResponse(Request $request)
{
 try {
    $productId = env('PayThru_business_productid');
    $response = $request->all();
    $dataEncode = json_encode($response);
    $data = json_decode($dataEncode);
    $modelType = "Business";

    Log::info("Starting webhookBusinessResponse");
    Log::info("Starting webhookBusinessResponse", ['data' => $data, 'modelType' => $modelType]);
    if ($data->notificationType == 1) {
        $buisness = businessTransaction::where('paymentReference', $data->transactionDetails->paymentReference)->first();

        $product_action = "payment";
        $referral = ReferralSetting::where('status', 'active')
            ->latest('updated_at')
            ->first();
        if ($referral) {
            $this->referral->checkSettingEnquiry($modelType, $product_action);
        }

//	$minus_residual = $business->minus_residual;
        if ($buisness) {
//	    $existing_minus_residual = $buisness->minus_residual ?? 0;
  //          $new_minus_residual = $existing_minus_residual + $data->transactionDetails->residualAmount;

            $buisness->payThruReference = $data->transactionDetails->payThruReference;
            $buisness->fiName = $data->transactionDetails->fiName;
            $buisness->status = $data->transactionDetails->status;
            $buisness->amount = $data->transactionDetails->amount;
            $buisness->responseCode = $data->transactionDetails->responseCode;
            $buisness->paymentMethod = $data->transactionDetails->paymentMethod;
            $buisness->commission = $data->transactionDetails->commission;
	    // Check if residualAmount is negative
		if ($data->transactionDetails->residualAmount < 0) {
    	    $buisness->negative_amount = $data->transactionDetails->residualAmount;
		} else {
    	    $buisness->negative_amount = 0;
		}
	    $buisness->residualAmount = $data->transactionDetails->residualAmount ?? 0;
            //$buisness->residualAmount = $data->transactionDetails->residualAmount;
            $buisness->resultCode = $data->transactionDetails->resultCode;
            $buisness->responseDescription = $data->transactionDetails->responseDescription;
	    $buisness->providedEmail = $data->transactionDetails->customerInfo->providedEmail;
            $buisness->providedName = $data->transactionDetails->customerInfo->providedName;
            $buisness->remarks = $data->transactionDetails->customerInfo->remarks;
    //        $business->minus_residual = $new_minus_residual;
            $buisness->save();
	   $activePayment = new Active([
                    'paymentReference' => $data->transactionDetails->paymentReference,
                    'product_id' => $productId,
                    'product_type' => $modelType
                ]);
           $activePayment->save();
                Log::info("Payment reference saved in ActivePayment table");

            Log::info("User buisness updated");
        } else {
            Log::info("User buisness not found for payment reference: " . $data->transactionDetails->paymentReference);
        }

        http_response_code(200);

    } elseif ($data->notificationType == 2) {
      if (isset($data->transactionDetails->transactionReferences[0])) {
          Log::info("Transaction references: " . json_encode($data->transactionDetails->transactionReferences));
	  $transactionReferences = $data->transactionDetails->transactionReferences[0];
	  Log::info("Transaction references: " .  $transactionReferences);
	  $upda = BusinessWithdrawal::where('transactionReferences', $transactionReferences)->first();
	  $updatePaybackWithdrawal = BusinessWithdrawal::where([
		'transactionReferences' => $transactionReferences,
		'uniqueId' => $upda->uniqueId
		])->first();

          $product_action = "withdrawal";
          $referral = ReferralSetting::where('status', 'active')
              ->latest('updated_at')
              ->first();
          if ($referral) {
              $this->referral->checkSettingEnquiry($modelType, $product_action);
          }

          if ($updatePaybackWithdrawal) {
              $updatePaybackWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
              $updatePaybackWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
	      // Set the status to "success"
              $updatePaybackWithdrawal->status = 'success';

              $updatePaybackWithdrawal->save();

              Log::info("Business withdrawal updated");
          } else {
              Log::info("Business withdrawal not found for transaction references: " . $data->transactionDetails->transactionReferences[0]);
          }
      } else {
          Log::info("Transaction references not found in the webhook data");
      }
  }

  http_response_code(200);
} catch (\Illuminate\Database\QueryException $e) {
  Log::error($e->getMessage());
  return response()->json(['error' => 'An error occurred'], 500);
}
}


    /**
     * Process a business withdrawal request and handle transaction settlement.
     *
     * This endpoint processes a withdrawal request by applying charges and settling the transaction,
     * updating the residual amount, and saving withdrawal details.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing input data.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the transaction reference and status.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException If token retrieval fails or other errors occur.
     *
     * @bodyParam amount int The amount to be withdrawn. Example: 5000
     * @bodyParam account_number string The account number for withdrawal. Example: "000123456789"
     * @bodyParam description string A description for the withdrawal. Example: "Business collection"
     * @bodyParam bank string The name of the bank. Example: "Bank of Example"
     *
     * @response 200 {
     *     "transactionReferences": "txn_123456",
     *     "status": "Success"
     * }
     *
     * @response 400 {
     *     "message": "You cannot withdraw an amount less than 100 after commission"
     * }
     *
     * @response 403 {
     *     "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @response 500 {
     *     "error": "Token retrieval failed"
     * }
     *
     * @post /business-settlements
     */

public function AzatBusinessCollection(Request $request)
    {
    $current_timestamp = now();
    $timestamp = strtotime($current_timestamp);
    $secret = env('PayThru_App_Secret');
    $productId = env('PayThru_business_productid');
    $hash = hash('sha512', $timestamp . $secret);
    $AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');
    $charges = env('PayThru_Withdrawal_Charges');

    $requestAmount = $request->amount;



    $latestCharge = Charge::orderBy('updated_at', 'desc')->first();
    $applyCharges = $this->chargeService->applyCharges($latestCharge);
    $requestAmount = $request->amount;


    $latestWithdrawal = BusinessTransaction::where('owner_id', auth()->user()->id)
        ->where('stat', 1)
        ->latest()
        ->pluck('minus_residual')
        ->first();

    if ($requestAmount < 100) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

    if ($latestWithdrawal !== null) {


        if ($requestAmount > $latestWithdrawal) {

            return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
        }


        if ($requestAmount > $latestWithdrawal) {

            return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
        }

        $minusResidual = $latestWithdrawal - $requestAmount;
    }
    $acct = $request->account_number;

   $getBankReferenceId = Bank::where('user_id', Auth::user()->id)->where('account_number', $acct)->first();
   //return $getBankReferenceId;

   $beneficiaryReferenceId = $getBankReferenceId->referenceId;

      $data = [
            'productId' => $productId,
            'amount' => $requestAmount - $latestCharge->charges,
            'beneficiary' => [
            'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];

        $token = $this->paythruService->handle();
      	  if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
        $url = $prodUrl;
        $urls = $url.'/transaction/settlement';


         $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
      ])->post($urls, $data );
      if($response->failed())
      {
        return false;
      }else{
       // $collection = json_decode($response->body(), true);


        BusinessTransaction::where('owner_id', auth()->user()->id)->where('stat', 1)
            ->latest()->update(['minus_residual' => $minusResidual]);

    // Save the withdrawal details
    $BusinessWithdrawal = new BusinessWithdrawal([
        'account_number' => $request->account_number,
        'description' => $request->description,
        'beneficiary_id' => auth()->user()->id,
        'amount' => $requestAmount - $latestCharge->charges,
        'bank' => $request->bank,
        'charges' => $charges,
        'uniqueId' => Str::random(10),
    ]);


        BusinessTransaction::where('owner_id', auth()->user()->id)->where('stat', 1)
            ->latest()->update(['minus_residual' => $minusResidual]);


          if ($applyCharges) {
              // Save the withdrawal details with charges
              $BusinessWithdrawal = new BusinessWithdrawal([
                  'account_number' => $request->account_number,
                  'description' => $request->description,
                  'beneficiary_id' => auth()->user()->id,
                  'amount' => $requestAmount - $latestCharge->charges,
                  'bank' => $request->bank,
                  'charges' => $latestCharge->charges,
                  'uniqueId' => Str::random(10),
              ]);
          } else {
              // Save the withdrawal details without charges
              $BusinessWithdrawal = new BusinessWithdrawal([
                  'account_number' => $request->account_number,
                  'description' => $request->description,
                  'beneficiary_id' => auth()->user()->id,
                  'amount' => $requestAmount,
                  'bank' => $request->bank,
                  'uniqueId' => Str::random(10),
              ]);
          }


    $BusinessWithdrawal->save();

  $collection = $response->object();
	$saveTransactionReference = BusinessWithdrawal::where('beneficiary_id', Auth::user()->id)->where('uniqueId', $BusinessWithdrawal->uniqueId)->update([
        'transactionReferences' => $collection->transactionReference,
	'status' => $collection->message
      ]);
	 return response()->json($saveTransactionReference, 200);
    }
}

    /**
     * Retrieve all business withdrawal transactions.
     *
     * This endpoint retrieves all withdrawal transactions associated with businesses.
     *
     * @response 200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "transaction_id": "WD12345",
     *       "amount": 2000.00,
     *       "status": "Completed"
     *     }
     *   ]
     * }
     *
     * @response 403 {
     *   "status": "error",
     *   "message": "You are not authorized to perform this action"
     * }
     *
     * @get /get-withdrawal-response
     */

public function getBusinessWithdrawalTransaction(Request $request)
{
//    $perPage = $request->query('per_page', 10);

  //  $page = $request->query('page', 1);

    $getWithdrawalTransaction = BusinessWithdrawal::where('beneficiary_id', Auth::user()->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Check if there are any items
    if ($getWithdrawalTransaction->isNotEmpty()) {
        return response()->json($getWithdrawalTransaction);
    } else {
        return response()->json([
            'message' => 'Transaction not found for this user'
        ], 404);
    }
}

    /**
     * Retrieve business withdrawal transactions for the authenticated user.
     *
     * This endpoint fetches all withdrawal transactions associated with the currently authenticated user.
     * Transactions are returned in descending order of creation date.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of withdrawal transactions or an error message.
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "account_number": "000123456789",
     *         "description": "Business collection",
     *         "beneficiary_id": 1,
     *         "amount": 5000,
     *         "bank": "Bank of Example",
     *         "charges": 100,
     *         "uniqueId": "abc123",
     *         "transactionReferences": "txn_123456",
     *         "status": "Success",
     *         "created_at": "2024-08-18T12:34:56Z",
     *         "updated_at": "2024-08-18T12:34:56Z"
     *     },
     *     // more transaction objects
     * ]
     *
     * @response 404 {
     *     "message": "Transaction not found for this user"
     * }
     *
     * @get /all-invoices-created-by-business-owner
     */

// Business Owner
    public function getAllInvoiceByABusinessOwner()
    {
        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByABusiness = businessTransaction::where('owner_id', $getUser)
->orderBy('created_at', 'desc')
->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByABusiness);

    }

    /**
     * Retrieve all customers under the business owned by the authenticated user.
     *
     * This endpoint retrieves a paginated list of all business transactions associated with the authenticated user.
     * The list includes transactions for a specific business and is paginated with a default page size.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing a paginated list of business transactions.
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "owner_id": 1,
     *             "name": "Product Name",
     *             "unique_code": "ABC123",
     *             "product_id": 1,
     *             "email": "customer@example.com",
     *             "transaction_amount": 100.00,
     *             "business_code": "BUS123",
     *             "description": "Product Description",
     *             "moto_id": 1,
     *             "bankName": "Bank Name",
     *             "bankCode": "123456",
     *             "account_number": "000123456789",
     *             "qty": 2,
     *             "vat": 7.50,
     *             "Grand_total": 107.50,
     *             "paymentReference": "txn_123456",
     *             "product_code": "XYZ789",
     *             "created_at": "2024-08-18T12:34:56Z",
     *             "updated_at": "2024-08-18T12:34:56Z"
     *         },
     *         // more transaction objects
     *     ],
     *     "first_page_url": "http://example.com/api/transactions?page=1",
     *     "from": 1,
     *     "last_page": 10,
     *     "last_page_url": "http://example.com/api/transactions?page=10",
     *     "next_page_url": "http://example.com/api/transactions?page=2",
     *     "path": "http://example.com/api/transactions",
     *     "per_page": 50,
     *     "prev_page_url": null,
     *     "to": 50,
     *     "total": 500
     * }
     */
    public function getAllCustomersUnderABusinessOwner(): \Illuminate\Http\JsonResponse
    {

        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);

    }

    /**
     * Count all invoices under the business owned by the authenticated user.
     *
     * This endpoint counts the total number of business transactions associated with the authenticated user.
     * It returns the total count of transactions for that user.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the count of business transactions.
     *
     * @response 200 {
     *     "count": 150
     * }
     *
     * @get /count-all-invoices-created-by-business-owner
     */
      public function countAllInvoiceByABusinessOwner()
    {
        $getUser = Auth::user()->id;
        $countAllInvoiceByABusinessOwner = businessTransaction::where('owner_id', $getUser)->count();
        return response()->json($countAllInvoiceByABusinessOwner);

    }
    //Business specific

    /**
     * Get all invoices for a specific business under the authenticated user.
     *
     * This endpoint retrieves a paginated list of business transactions for a specific business code,
     * owned by the authenticated user. The results are sorted by creation date in descending order.
     *
     * @param string $business_code The business code to filter invoices.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the paginated list of invoices.
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "owner_id": 1,
     *             "name": "Product Name",
     *             "unique_code": "ABC123",
     *             "product_id": 1,
     *             "email": "customer@example.com",
     *             "transaction_amount": 100.00,
     *             "business_code": "BUS123",
     *             "description": "Product description",
     *             "moto_id": 1,
     *             "bankName": "Bank Name",
     *             "bankCode": "BANK123",
     *             "account_number": "1234567890",
     *             "qty": 2,
     *             "vat": 7.50,
     *             "Grand_total": 107.50,
     *             "paymentReference": "REF123456",
     *             "product_code": "XYZ789",
     *             "created_at": "2024-08-15T12:34:56.000000Z",
     *             "updated_at": "2024-08-15T12:34:56.000000Z"
     *         }
     *     ],
     *     "first_page_url": "http://example.com/api/invoices?business_code=BUS123&page=1",
     *     "from": 1,
     *     "last_page": 10,
     *     "last_page_url": "http://example.com/api/invoices?business_code=BUS123&page=10",
     *     "links": [
     *         {
     *             "url": null,
     *             "label": "&laquo; Previous",
     *             "active": false
     *         },
     *         {
     *             "url": "http://example.com/api/invoices?business_code=BUS123&page=1",
     *             "label": "1",
     *             "active": true
     *         },
     *         {
     *             "url": "http://example.com/api/invoices?business_code=BUS123&page=2",
     *             "label": "2",
     *             "active": false
     *         }
     *     ],
     *     "next_page_url": "http://example.com/api/invoices?business_code=BUS123&page=2",
     *     "path": "http://example.com/api/invoices",
     *     "per_page": 50,
     *     "prev_page_url": null,
     *     "to": 50,
     *     "total": 500
     * }
     */
     public function getAllInvoiceByASpecificBusiness($business_code)
    {

        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)
->orderBy('created_at', 'desc')
->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);
    }

    /**
     * Get all transactions for a specific business under the authenticated user.
     *
     * This endpoint retrieves a paginated list of transactions for a specific business code,
     * owned by the authenticated user. The results are sorted by creation date in descending order.
     *
     * @param string $business_code The business code to filter transactions.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the paginated list of transactions.
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "owner_id": 1,
     *             "name": "Transaction Name",
     *             "unique_code": "TXN123",
     *             "product_id": 1,
     *             "email": "customer@example.com",
     *             "transaction_amount": 100.00,
     *             "business_code": "BUS123",
     *             "description": "Transaction description",
     *             "moto_id": 1,
     *             "bankName": "Bank Name",
     *             "bankCode": "BANK123",
     *             "account_number": "1234567890",
     *             "qty": 2,
     *             "vat": 7.50,
     *             "Grand_total": 107.50,
     *             "paymentReference": "REF123456",
     *             "product_code": "XYZ789",
     *             "created_at": "2024-08-15T12:34:56.000000Z",
     *             "updated_at": "2024-08-15T12:34:56.000000Z"
     *         }
     *     ],
     *     "first_page_url": "http://example.com/api/transactions?business_code=BUS123&page=1",
     *     "from": 1,
     *     "last_page": 10,
     *     "last_page_url": "http://example.com/api/transactions?business_code=BUS123&page=10",
     *     "links": [
     *         {
     *             "url": null,
     *             "label": "&laquo; Previous",
     *             "active": false
     *         },
     *         {
     *             "url": "http://example.com/api/transactions?business_code=BUS123&page=1",
     *             "label": "1",
     *             "active": true
     *         },
     *         {
     *             "url": "http://example.com/api/transactions?business_code=BUS123&page=2",
     *             "label": "2",
     *             "active": false
     *         }
     *     ],
     *     "next_page_url": "http://example.com/api/transactions?business_code=BUS123&page=2",
     *     "path": "http://example.com/api/transactions",
     *     "per_page": 50,
     *     "prev_page_url": null,
     *     "to": 50,
     *     "total": 500
     * }
     *
     * @get /get-all-transactions-created-by-a-specific-business/{business_code}
     */
     public function getAllTransactionsByASpecificBusiness($business_code)
    {
        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)
->orderBy('created_at', 'desc')
->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);
    }

    /**
     * Get all customers under a specific business code for the authenticated user.
     *
     * This endpoint retrieves a list of customers associated with a specific business code,
     * owned by the authenticated user. The results include customer names, emails, and phone numbers,
     * and are sorted by creation date in descending order.
     *
     * @param string $business_code The business code to filter customers.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of customers.
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "customer_name": "John Doe",
     *             "customer_email": "john.doe@example.com",
     *             "customer_phone": "+1234567890"
     *         },
     *         {
     *             "customer_name": "Jane Smith",
     *             "customer_email": "jane.smith@example.com",
     *             "customer_phone": "+0987654321"
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "message": "No customers found for this business code"
     * }
     *
     * @get /get-all-customers-under-a-specific-business/{business_code}
     */
    public function getAllCustomersUnderASpecificBusiness($business_code)
    {
        $getUser = Auth::user()->id;

        $getAllCustomersUnderASpecificBusiness = Customer::where('owner_id', $getUser)->where('customer_code', $business_code)->select('customer_name', 'customer_email', 'customer_phone')->latest()->get();

        return response()->json($getAllCustomersUnderASpecificBusiness);
    }

    //Get all business customers


    /**
     * Get all invoices sent to a particular customer by the authenticated user.
     *
     * This endpoint retrieves a paginated list of invoices sent to a specific customer email,
     * where the transaction is associated with a particular MOTO ID and owned by the authenticated user.
     * The results are sorted by creation date in descending order.
     *
     * @param string $customerEmail The email of the customer to filter invoices.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the paginated list of invoices.
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "owner_id": 1,
     *             "name": "Invoice Name",
     *             "unique_code": "INV123",
     *             "product_id": 1,
     *             "email": "customer@example.com",
     *             "transaction_amount": 150.00,
     *             "business_code": "BUS123",
     *             "description": "Invoice description",
     *             "moto_id": 2,
     *             "bankName": "Bank Name",
     *             "bankCode": "BANK123",
     *             "account_number": "1234567890",
     *             "qty": 3,
     *             "vat": 10.00,
     *             "Grand_total": 160.00,
     *             "paymentReference": "REF123456",
     *             "product_code": "XYZ789",
     *             "created_at": "2024-08-15T12:34:56.000000Z",
     *             "updated_at": "2024-08-15T12:34:56.000000Z"
     *         }
     *     ],
     *     "first_page_url": "http://example.com/api/invoices?customer_email=customer@example.com&page=1",
     *     "from": 1,
     *     "last_page": 10,
     *     "last_page_url": "http://example.com/api/invoices?customer_email=customer@example.com&page=10",
     *     "links": [
     *         {
     *             "url": null,
     *             "label": "&laquo; Previous",
     *             "active": false
     *         },
     *         {
     *             "url": "http://example.com/api/invoices?customer_email=customer@example.com&page=1",
     *             "label": "1",
     *             "active": true
     *         },
     *         {
     *             "url": "http://example.com/api/invoices?customer_email=customer@example.com&page=2",
     *             "label": "2",
     *             "active": false
     *         }
     *     ],
     *     "next_page_url": "http://example.com/api/invoices?customer_email=customer@example.com&page=2",
     *     "path": "http://example.com/api/invoices",
     *     "per_page": 50,
     *     "prev_page_url": null,
     *     "to": 50,
     *     "total": 500
     * }
     *
     * @response 404 {
     *     "message": "No invoices found for this customer email"
     * }
     *
     * @get /customer-invoice/{customerEmail}
     */
    public function getAllInvoiceSentToAParticularCustomer($customerEmail)
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllInvoiceSentToAParticularCustomer = businessTransaction::where('email', $customerEmail)->where('moto_id', 2)->where('owner_id', $AuthUser)->latest()
->orderBy('created_at', 'desc')
->paginate($pageNumber);
        return response()->json($getAllInvoiceSentToAParticularCustomer);

    }

    /**
     * Process payments for a business using MPOS.
     *
     * This method handles the payment requests either for a single product or multiple products based on the
     * provided unique codes, quantities, and amounts. It validates the input data, checks business existence,
     * processes payments through an external service, and saves the transaction details in the database.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing payment details.
     *     - unique_code: array of unique product codes (e.g., ['ABC123', 'XYZ456'])
     *     - quantity: array of quantities corresponding to each unique code (e.g., [2, 3])
     *     - amount: array of amounts corresponding to each unique code (e.g., [50.00, 75.00])
     * @param string $business_code The business code associated with the payment.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the success or failure of the payment process.
     *
     * @response 200 {
     *     "data": {
     *         "vat": 7.50,
     *         "quantity": 10,
     *         "business_email": "business@example.com",
     *         "business_name": "Business Name",
     *         "transaction_amount": 107.50,
     *         "created_at": "2024-08-18T12:00:00Z",
     *         "pay_link": "https://pay.example.com/transaction/1234567890"
     *     },
     *     "exception": null
     * }
     *
     * @response 400 {
     *     "message": "Invalid input data"
     * }
     *
     * @response 404 {
     *     "message": "Business not found"
     * }
     */

    public function mposPay(Request $request, $business_code)
    {
        if (!empty($this->mPos)) {
           $mposPayment = $this->mPos->mposPay($request, $business_code);
            return response()->json($mposPayment);
        }
    }


    /**
     * Process a one-time payment using MPOS.
     *
     * This method handles the payment request for a single product with a specified amount. It validates the
     * payment data, interacts with the payment service, and saves the transaction details in the database.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing payment details.
     *     - amount: The amount to be paid (e.g., 100.00)
     * @param string $business_code The business code associated with the payment.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with payment details or error messages.
     *
     * @response 200 {
     *     "data": {
     *         "business_email": "business@example.com",
     *         "business_name": "Business Name",
     *         "transaction_amount": 100.00,
     *         "created_at": "2024-08-18T12:00:00Z",
     *         "payLink": "https://pay.example.com/transaction/1234567890"
     *     },
     *     "exception": null
     * }
     *
     * @response 400 {
     *     "exception": "Transaction failed."
     * }
     *
     * @response 404 {
     *     "exception": "Business not found."
     * }
     *
     * @response 500 {
     *     "exception": "Unexpected error occurred."
     * }
     */

     public function mposOneTimePay(Request $request, $business_code)
    {

        if (!empty($this->mPos)) {
            $mposPayment = $this->mPos->mPosOneTimePay($request, $business_code);
            return response()->json($mposPayment);
        }

        return response()->json(['message' => 'MPOS Payment service is not available.'], 503);
    }


    /**
     * Retrieve MPOS transactions for a specific business.
     *
     * This method fetches all transactions associated with the given business code. It returns a list of transactions
     * if available, or an error message if no transactions are found or if the MPOS service is unavailable.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object. This can include any relevant query parameters.
     * @param string $business_code The business code for which to retrieve transactions.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the transaction data or an error message.
     *
     * @response 200 {
     *     "status": "success",
     *     "data": [
     *         {
     *             "transaction_id": 1,
     *             "transaction_amount": 100.00,
     *             "created_at": "2024-08-18T12:00:00Z",
     *             "description": "Payment for product XYZ"
     *         },
     *         // more transactions
     *     ],
     *     "message": "Transactions retrieved successfully."
     * }
     *
     * @response 404 {
     *     "status": "error",
     *     "message": "No transactions found for the specified business code."
     * }
     *
     * @response 503 {
     *     "status": "error",
     *     "message": "MPOS Payment service is not available for this business."
     * }
     */

public function getMposPerBusiness(Request $request, $business_code): JsonResponse
{
    if (!empty($this->mPos)) {
        $transactions = $this->mPos->getAllTransactionPerBusiness($request, $business_code);

        if ($transactions) {
            // Use resource collection to format the response
            return response()->json([
                'status' => 'success',
                'data' => $transactions,
                'message' => 'Transactions retrieved successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No transactions found for the specified business code.'
        ], 404);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'MPOS Payment service is not available for this business.'
    ], 503);
}


    /**
     * Retrieve a specific MPOS transaction by its payment reference.
     *
     * This method fetches the transaction details for a given payment reference. It returns the transaction data
     * if found, or an appropriate error message if the transaction does not exist or if the MPOS service is unavailable.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object. This can include any relevant query parameters.
     * @param string $paymentReference The payment reference to look up the transaction.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the transaction data or an error message.
     *
     * @response 200 {
     *     "status": "success",
     *     "data": {
     *         "transaction_id": 1,
     *         "transaction_amount": 100.00,
     *         "created_at": "2024-08-18T12:00:00Z",
     *         "description": "Payment for product XYZ",
     *         "paymentReference": "abc123"
     *     },
     *     "message": "Transaction retrieved successfully."
     * }
     *
     * @response 404 {
     *     "status": "error",
     *     "message": "No transaction found for the specified payment reference."
     * }
     *
     * @response 503 {
     *     "status": "error",
     *     "message": "MPOS Payment service is not available for this business."
     * }
     */
public function getMposPerPaymentReference(Request $request, $paymentReference): JsonResponse
{
    if (!empty($this->mPos)) {
        $transaction = $this->mPos->getTransactionPerPaymentReference($request, $paymentReference);

        if ($transaction) {
            // Use resource to format the response
            return response()->json([
                'status' => 'success',
                'data' => $transaction,
                'message' => 'Transaction retrieved successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No transaction found for the specified payment reference.'
        ], 404);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'MPOS Payment service is not available for this business.'
    ], 503);
}




}
