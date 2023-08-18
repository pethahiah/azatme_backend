<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
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
use PDF;
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



class BusinessTransactionController extends Controller
{
    //

    public $paythruService;

    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }

    public function creatProduct(Request $request)
    {
        $getAdmin = Auth::user()->id;
        //return $getAdmin
   	$getBusinessVatOption = Business::where('owner_id', Auth::user()->id)->first();

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'unique_code' => Str::random(10),
            'business_code'=> $request->business_code,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'amount' => $request->amount,
            'user_id' => Auth::user()->id
        ]);

        return response()->json($product);

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


    public function startBusinessTransaction(Request $request, $product_id, $business_code)
    {
      //return response()->json($request->input());
      $product = product::findOrFail($product_id);
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
      $secret = env('PayThru_App_Secret');
      $generalPath = env('file_public_path');
      $PayThru_productId = env('PayThru_business_productid');
      //$product = Product::findOrFail($product_id);
      $prodUrl = env('PayThru_Base_Live_Url');

      //return $generalPath;
      $input['email'] = $request->input('email');
      $emails = $request->email;
      if($emails)
      {
      $emailArray = (explode(';', $emails));
      $count = count($emailArray);
      foreach ($emailArray as $key => $em) {
          //process each user here as each iteration gives you each email
      $user = Customer::where('customer_email', $em)->first();
     $user->customer_email;

    $payers = [];
    $totalpayable = 0;
    $payable = 0;

    $vat = 0;
    $getBusinessVatOption = Business::where('owner_id', Auth::user()->id)->where('business_code', $business_code)->first();
//return $getBusinessVatOption;
    $getVatOption = $getBusinessVatOption->vat_option;

    if($getVatOption == 'Yes')
    {
      $vat = 0.75;
    }elseif($getVatOption == 'No')
   {
     $vat = 0;
   }

 // Ensure that $request->quantity is numeric
    $quantity = is_numeric($request->quantity) ? $request->quantity : 0;

    // Ensure that $product->amount is numeric
    $amount = is_numeric($product->amount) ? $product->amount : 0;

    // Calculate vat and Grand_total with well-formed numeric values
    $vatAmount = $amount * $quantity * $vat;
    $grandTotal = ($amount * $quantity) + $vatAmount;
    $hash = hash('sha512', $timestamp . $secret);
    $hashSign = hash('sha512', $grandTotal . $secret);
        
        if($request['moto_id'] == 1)
        {
            $info = BusinessTransaction::create([
                'owner_id' => Auth::user()->id,
                'name' => $product->name,
                'unique_code' => $product->unique_code,
                'product_id' => $product->id,
                'email' => $user->customer_email,
                'transaction_amount' => $product->amount,
                'business_code' => $product->business_code,
                'description' => $product['description'],
                'moto_id' => $request['moto_id'],
                'bankName' => $request['bankName'],
                'bankCode' => $request['bankCode'],
		'qty' => $quantity,
                'vat' => $vatAmount,
                'Grand_total' => $grandTotal,
                'account_number' => $request['account_number'],
              ]);
              
              //return $info;

            $token = $this->paythruService->handle();
              
            $data = [
                'amount' => $grandTotal,
                'productId' => $PayThru_productId,
                'transactionReference' => time().$product->id,
                'paymentDescription' => $product->description,
                'paymentType' => 1,
                'sign' => $hashSign,
                'displaySummary' => false,
               
                ];
//return $data;
    $url = $prodUrl;
    $urls = $url.'/transaction/create';

 $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
])->post($urls, $data );
//return $response;
              if($response->failed())
              {
                return false;
              }else{
                $transaction = json_decode($response->body(), true);
                $paylink = $transaction['payLink'];
           
                  Mail::to($user->customer_email)->send(new BusinessPaylinkMail($paylink));
                //}
                 if($paylink)
            {
              $getLastString = (explode('/', $paylink));
              $now = end($getLastString);
              //return $now;
        $businessPayReference = businessTransaction::where(['email' => $user->customer_email, 'product_id' => $product->id, 'owner_id' => Auth::user()->id])->update([
            'paymentReference' => $now,
        ]);
      
              }
             
              return response()->json($transaction);
            }

        }elseif($request['moto_id'] == 2)
        
        {
            $latest = businessTransaction::latest()->first();

        //return $product;

            $invoice_number = "";
            if (empty($latest)) {
                $invoice_number = 'PIN';
            }else{
                $string = preg_replace("/[^0-9\.]/", '', $latest->invoice_number);
                $invoice_number = 'PIN'. ++$string;
            }
            
           // $getVat = Vat::findOrFail($vat_id);

            $current = \Carbon\Carbon:: now();
            $invoice = businessTransaction::create([
                'owner_id' => Auth::id(),
                'name' => $product->name,
                'unique_code' => $product->unique_code,
                'email' => $em,
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
                'issue_date' => \Carbon\Carbon::now(),      
        ]);
        //return $invoice;

        $token = $this->paythruService->handle();
       $data = [
        'amount' => $grandTotal,
        'productId' => $PayThru_productId,
        'transactionReference' => time().$product->id,
        'paymentDescription' => $product->description,
        'paymentType' => 1,
        'sign' => $hashSign,
        'displaySummary' => false,
        ];
      
      //return $token;
        $url = $prodUrl;
        $urls = $url.'/transaction/create';

         $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
        ])->post($urls, $data );
//	return $response;
                      if($response->failed())
                      {
                        return false;
                      }else{
                        $transaction = json_decode($response->body(), true);
                        $paylink = $transaction['payLink'];
                        
                        if($paylink)
            {
              $getLastString = (explode('/', $paylink));
              $now = end($getLastString);
              //return $now;
        $businessPayReference = businessTransaction::where(['email' => $user->customer_email, 'product_id' => $product->id, 'owner_id' => Auth::user()->id])->update([
            'paymentReference' => $now,
        ]);
      }
      
      
     $paylink = $transaction['payLink'];
     
         $getBusiness = User::where('id', Auth::user()->id)->first();
	 $business = Business::where('owner_id', Auth::user()->id)->first();
// 	return	$business->business_logo;
         $InvoiceTrans = businessTransaction::where('unique_code', $product->unique_code)->first();
                         // return $InvoiceTrans;
                          
         $sum = $InvoiceTrans->vat;
         $sum1 = $InvoiceTrans->amount;
         $cusInvoEmail = $InvoiceTrans->email;
         $getUserInvo = Customer::where('customer_email', $cusInvoEmail)->first();
                          
         $pdf = PDF::loadView('generate/invoice', compact('invoice', 'getBusiness', 'business', 'getUserInvo', 'paylink'));
                       // $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

       			

// Get the URL path of the saved PDF

       $filename = 'invoice_' . $product_id . '_' . time() . '.pdf';

// Save the PDF to your server's storage directory
      \Storage::disk('public')->put($filename, $pdf->output());

// Get the public URL of the saved PDF
    $pdf_url = \Storage::disk('public')->url($filename);
  
       return response()->json([
           'status' => 'Successful',
           'link' => $pdf_url]);

                        //return $pdf->download('invoice.pdf');
                        //}
                      }
                    }
    
}
}
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

    if ($data->notificationType == 1) {
        $buisness = businessTransaction::where('paymentReference', $data->transactionDetails->paymentReference)->first();
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
            $buisness->residualAmount = $data->transactionDetails->residualAmount;
            $buisness->resultCode = $data->transactionDetails->resultCode;
            $buisness->responseDescription = $data->transactionDetails->responseDescription;
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
	  $upda = BusinessWithdrawal::where('transactionReferences', $transactionReferences)->first();
	  $updatePaybackWithdrawal = BusinessWithdrawal::where([
		'transactionReferences' => $transactionReferences,
		'uniqueId' => $upda->uniqueId
		])->first();

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
    $productId = env('PayThru_expense_productid');
    $hash = hash('sha512', $timestamp . $secret);
    $AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');
    $charges = env('PayThru_Withdrawal_Charges');

    $userBusinessTransactions = BusinessTransaction::where('owner_id', auth()->user()->id)
        ->sum('residualAmount');

    // Step 1: Subtract residualAmount from the request->amount and update it in minus_residual column
    $requestAmount = $request->amount;
    $minusResidual = $userBusinessTransactions - $requestAmount;

    // Check if the first withdrawal request or consecutive withdrawal
    $latestWithdrawal = BusinessTransaction::where('owner_id', auth()->user()->id)
        ->latest('updated_at')
        ->first();

    if ($latestWithdrawal) {
        // Consecutive withdrawal request
        $latestMinusResidual = $latestWithdrawal->minus_residual;
        if ($requestAmount > $latestMinusResidual) {
            // Step 4: Request amount exceeds latest minus_residual
            $remainingAmount = $requestAmount - $latestMinusResidual;
           // $remainingMinusResidual = $userBusinessTransactions - $remainingAmount;
            if ($remainingAmount < 0) {
                return response()->json(['message' => 'You do not have sufficient amount in your Business Account'], 400);
            }
            $minusResidual = $remainingAmount;
        } else {
            // Step 3: Update minus_residual for consecutive withdrawal
            $minusResidual = $latestMinusResidual - $requestAmount;
        }
    } else {
        // Step 3: First request to withdraw
        if ($requestAmount > $userBusinessTransactions) {
            return response()->json(['message' => 'You do not have sufficient amount in your RefundMe'], 400);
        }
    }

        BusinessTransaction::where('onwer_id', auth()->user()->id)->update(['minus_residual' => $minusResidual]);
 $BusinessWithdrawal = new BusinessWithdrawal([
        'account_number' => $request->account_number,
        'description' => $request->description,
        'beneficiary_id' => auth()->user()->id,
        'amount' => $requestAmount - $charges,
        'bank' => $request->bank,
        'charges' => $charges,
        'uniqueId' => Str::random(10),
       // 'minus_residual' => $minusResidual, // Update minus_residual here
    ]);

    $BusinessWithdrawal->save();
   // $businessAmountWithdrawn = $request->amount - $charges;
    $acct = $request->account_number;
    
   $getBankReferenceId = Bank::where('user_id', Auth::user()->id)->where('account_number', $acct)->first();
   //return $getBankReferenceId;
   
   $beneficiaryReferenceId = $getBankReferenceId->referenceId;
 
      $data = [
            'productId' => $productId,
            'amount' => $request->amount,
            'beneficiary' => [
            'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];
        
        $token = $this->paythruService->handle();
      //return $token;
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
       // return $collection;
	 $collection = $response->object();
	$saveTransactionReference = BusinessWithdrawal::where('beneficiary_id', Auth::user()->id)->where('uniqueId', $BusinessWithdrawal->uniqueId)->update([
        'transactionReferences' => $collection->transactionReference,
//	'minus_residual' => $minusResidual,
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
            ], 403);
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
    
    public function getAllCustomersUnderABusinessOwner()
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
        return response()->json($countAllInvoiceByABusinessOnwer);

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
    
    
    public function businessSettlementWebhookResponse(Request $request)
   {
       
        $productId = env('PayThru_business_productid');
       
        $response = $request->all();
       $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
        if($data->notificationType == 2){
         // return "good";
        $updateBusinessWithdrawal = BusinessWithdrawal::where(['transactionReferences'=> $data->transactionDetails->transactionReferences, $productId => $data->transactionDetails->productId])->update([
            'paymentAmount' => $data->transactionDetails->paymentAmount,
            'recordDateTime' => $data->transactionDetails->recordDateTime,
        ]);
          Log::info("payback settlement done");
          http_response_code(200);

        }else
       return response([
                'message' => 'notificationtype is not 2'
            ], 401);
    }
    
    
    
}
