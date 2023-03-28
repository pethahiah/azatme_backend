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
use Mail;
use Illuminate\Support\Str;
use PDF;
use App\Notifications\BusinessNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use App\Mail\BusinessPaylinkMail;
use App\Setting;
use Illuminate\Support\Facades\Log;
use App\Services\PaythruService;

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
        //return $getAdmin;

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
   
     public function getAllProductsPerBusiness()
    {
        $pageNumber = 50;
        $AuthUser = Auth::user()->id;
        $getAllProductsPerBusiness = product::where('user_id', $AuthUser)->latest()->paginate($pageNumber);
        return response()->json($getAllProductsPerBusiness);
    }
    

    public function startBusinessTransaction(Request $request, $product_id)
    {
      //return response()->json($request->input());
      $product = product::findOrFail($product_id);
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
      $secret = env('PayThru_App_Secret');
      $generalPath = env('file_public_path');
      $PayThru_productId = env('PayThru_business_productid');
      $hash = hash('sha512', $timestamp . $secret);
      $amt = $product->amount;
      $hashSign = hash('sha512', $amt . $secret);
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

        if($request['split_method_id'] == 1)
        {
            $payable = $product->amount;
  
        } elseif($request['split_method_id'] == 2)
        {
          if(isset($request->percentage))
          {
            $payable = $product->amount*$request->percentage/100;
          }elseif(isset($request->percentage_per_user))
          {
            $ppu = json_decode($request->percentage_per_user);
            $payable = $ppu->$em*$product->amount/100;
          }
        }
        
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
                'account_number' => $request['account_number'],
              ]);
              
              //return $info;

            $token = $this->paythruService->handle();
              
            $data = [
                'amount' => $info->transaction_amount,
                'productId' => $PayThru_productId,
                'transactionReference' => time().$product->id,
                'paymentDescription' => $product->description,
                'paymentType' => 1,
                'sign' => $hashSign,
                'displaySummary' => false,
               
                ];

    $url = $prodUrl;
    $urls = $url.'/transaction/create';

 $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
])->post($urls, $data );
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
                'qty' => $request->quantity,
                'vat' => $product->amount*7.5/100,
                'Grand_total' => $product->amount+$product->amount*7.5/100,
                'due_days' => $request->due_days,
                'due_date' => $current->addDays($request->due_days),
                'issue_date' => \Carbon\Carbon::now(),      
        ]);
        
        //return $invoice;

        $token = $this->paythruService->handle();
    
        
       $data = [
        'amount' => $product->amount,
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
                        $InvoiceTrans = businessTransaction::where('unique_code', $product->unique_code)->first();
                         // return $InvoiceTrans;
                          
                          $sum = $InvoiceTrans->vat;
                          $sum1 = $InvoiceTrans->amount;
                          $cusInvoEmail = $InvoiceTrans->email;
                          $getUserInvo = Customer::where('customer_email', $cusInvoEmail)->first();
                          
                          $pdf = PDF::loadView('generate/invoice', compact('invoice', 'getBusiness', 'getUserInvo', 'paylink'));
                       // $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

        // Generate a unique filename for the PDF
        $filename = 'invoice_' . $product_id . '_' . time() . '.pdf';
        

        // Save the PDF to your server's storage directory
        $pdf->save(public_path($filename));

        // Generate a download link for the PDF
        $path = "$generalPath/$filename";
        //$link = url('/api/invoices/' . $product_id . '/pdf/' . $filename);
        //return $path;
       return response()->json([
           'status' => 'Successful',
           'link' => $path]);

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
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
        //$da = $data->transactionDetails->status;
        Log::info("webhook-data" . json_encode($data));
        if($data->transactionDetails->status == 'Successful'){
         // return "good";
        $userExpense = businessTransaction::where('paymentReference', $data->transactionDetails->paymentReference)->update([
            'payThruReference' => $data->transactionDetails->payThruReference,
            'fiName' => $data->transactionDetails->fiName,
            'status' => $data->transactionDetails->status,
            'amount' => $data->transactionDetails->amount,
            'responseCode' => $data->transactionDetails->responseCode,
            'paymentMethod' => $data->transactionDetails->paymentMethod,
            'commission' => $data->transactionDetails->commission,
            'residualAmount' => $data->transactionDetails->residualAmount,
            'resultCode' => $data->transactionDetails->resultCode,
            'responseDescription' => $data->transactionDetails->responseDescription,
           
        ]);
          Log::info("done for Buz");
          
          http_response_code(200);

        }else
        
        Log::info("Not successful for Buz");
       return response([
                'message' => 'No response recieve for this transaction'
            ], 401);
    }


public function AzatBusinessCollection(Request $request, $BusinessTransactionId)
    {
      
      $current_timestamp= now();
     // return $current_timestamp;
      $timestamp = strtotime($current_timestamp);
      $secret = env('PayThru_App_Secret');
      $productId = env('PayThru_business_productid');
      //return $productId;
      $hash = hash('sha512', $timestamp . $secret);
      //return $hash;
      $AppId = env('PayThru_ApplicationId');
      $prodUrl = env('PayThru_Base_Live_Url');
     
      $productAmount = businessTransaction::where('owner_id', Auth::user()->id)->where('product_id', $BusinessTransactionId)->whereNotNull('amount')->first();
      $amount = $productAmount->amount;
   
  $getAdmin = Auth::user();
  $getAd = $getAdmin -> usertype;
  if($getAd === 'merchant')
  { 
      $BusinessWithdrawal = new BusinessWithdrawal([
        'account_number' => $request->account_number,
        'description' => $request->description,
        'product_id' => $productAmount ->id,
        'beneficiary_id' => Auth::user()->id,
        'amount' => $request->amount,
        'bank' => $request->bank
        ]);
        
        $getUserBusinessTransactions = BusinessTransaction::where('owner_id', $getAdmin->id)->sum('residualAmount');
        
        if(($request->amount) > $amount)
        {
            if(($request->amount) > $getUserBusinessTransactions)
            {
                return response([
            'message' => 'You dont not have sufficient balance in your business account'
        ], 403);
            }else{
          return response([
            'message' => 'Please enter correct product amount'
        ], 403);
        }
        }
        
        $BusinessWithdrawal->save();
    
    $acct = $request->account_number;
    
   $getBankReferenceId = Bank::where('user_id', Auth::user()->id)->where('account_number', $acct)->first();
   //return $getBankReferenceId;
   
   $beneficiaryReferenceId = $getBankReferenceId->referenceId;
 
      $data = [
            'productId' => $productId,
            'amount' => $amount,
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
        $collection = json_decode($response->body(), true);
        return $collection;
    }
  }else{
    return response()->json('Auth user is not a merchant');
 }
}


// Business Owner

    public function getAllInvoiceByABusinessOwner()
    {
        $getUser = Auth::user()->id;
        $pageNumber = 50;
        $getAllInvoiceByABusiness = Invoice::where('owner_id', $getUser)->latest()->paginate($pageNumber);
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
                'message' => 'data does not exists'
            ], 401);
    }
    
    
    
}
