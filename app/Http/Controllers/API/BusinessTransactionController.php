<?php

namespace App\Http\Controllers\API;


use App\charge;
use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ChargeService;
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




class BusinessTransactionController extends Controller
{
    //

    public $referral;
    public $paythruService;

    public $chargeService;

    public function __construct(PaythruService $paythruService, Referrals $referral, ChargeService $chargeService)
    {
        $this->paythruService = $paythruService;
        $this->referral = $referral;
        $this->chargeService = $chargeService;
    }

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


     public function getAllProductsPerBusinessMerchant()
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllProductsPerBusinessMerchant = product::where('user_id', $AuthUser)->latest()->paginate($pageNumber);
        return response()->json($getAllProductsPerBusinessMerchant);
    }

    public function getProductsPerBusiness($businessCode)
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllProductsPerBusiness = product::where('business_code', $businessCode)->latest()->paginate($pageNumber);
        return response()->json($getAllProductsPerBusiness);
    }


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

                     //   return $invoice;

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

                    //return $info;
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
		//	echo $totalQuantity;
		//	echo $totalAmount;
			 $InvoiceTran = businessTransaction::where('product_code', $info->product_code)->get();
//                        return $InvoiceTran;
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
        $referral = ReferralSetting::where('status', 'active')
            ->latest('updated_at')
            ->first();
        if ($referral) {
            $this->referral->checkSettingEnquiry($modelType);
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

          $referral = ReferralSetting::where('status', 'active')
              ->latest('updated_at')
              ->first();
          if ($referral) {
              $this->referral->checkSettingEnquiry($modelType);
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


public function AzatBusinessCollection(Request $request)
    {
    $current_timestamp = now();
    $timestamp = strtotime($current_timestamp);
    $secret = env('PayThru_App_Secret');
    $productId = env('PayThru_business_productid');
    $hash = hash('sha512', $timestamp . $secret);
    $AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');

    $latestCharge = Charge::orderBy('updated_at', 'desc')->first();
    $applyCharges = $this->chargeService->applyCharges($latestCharge);
    $requestAmount = $request->amount;


    $latestWithdrawal = BusinessTransaction::where('owner_id', auth()->user()->id)
        ->where('stat', 1)
        ->latest()
        ->pluck('minus_residual')
        ->first();

    if ($requestAmount < 130) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

    if ($latestWithdrawal !== null) {

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

	public function getBusinessWithdrawalTransaction()
        {
        $getWithdrawalTransaction = BusinessWithdrawal::where('beneficiary_id', Auth::user()->id)->get();
        if($getWithdrawalTransaction->count() > 0)
        {
        return response()->json($getWithdrawalTransaction);
        }else{
         return response([
                'message' => 'transaction not found for this user'
            ], 404);
                }
        }



// Business Owner
    public function getAllInvoiceByABusinessOwner()
    {
        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByABusiness = businessTransaction::where('owner_id', $getUser)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByABusiness);

    }

    public function getAllCustomersUnderABusinessOwner(): \Illuminate\Http\JsonResponse
    {

        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);

    }


      public function countAllInvoiceByABusinessOwner()
    {
        $getUser = Auth::user()->id;
        $countAllInvoiceByABusinessOwner = businessTransaction::where('owner_id', $getUser)->count();
        return response()->json($countAllInvoiceByABusinessOwner);

    }
    //Business specific

     public function getAllInvoiceByASpecificBusiness($business_code)
    {

        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);
    }

     public function getAllTransactionsByASpecificBusiness($business_code)
    {
        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByASpecificBusiness = businessTransaction::where('owner_id', $getUser)->where('business_code', $business_code)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceByASpecificBusiness);
    }

    public function getAllCustomersUnderASpecificBusiness($business_code)
    {
        $getUser = Auth::user()->id;

        $getAllCustomersUnderASpecificBusiness = Customer::where('owner_id', $getUser)->where('customer_code', $business_code)->select('customer_name', 'customer_email', 'customer_phone')->latest()->get();

        return response()->json($getAllCustomersUnderASpecificBusiness);
    }

    //Get all business customers
    public function getAllInvoiceSentToAParticularCustomer($customerEmail)
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllInvoiceSentToAParticularCustomer = businessTransaction::where('email', $customerEmail)->where('moto_id', 2)->where('owner_id', $AuthUser)->latest()->paginate($pageNumber);
        return response()->json($getAllInvoiceSentToAParticularCustomer);

    }
}
