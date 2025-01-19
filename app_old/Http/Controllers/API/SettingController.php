<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Setting;
use Illuminate\Support\Facades\Http;



class SettingController extends Controller
{
    //
    public function param(Request $request)
    {

        $setting = Setting::create([
            'token' => $request->token,
            'prodUrl' => $request->prodUrl,
          
        ]);

        return response()->json($setting);

    }
    
     public function returnToken(Request $request)
    {
        $token = Setting::where('id', 1)->first()->prodUrl;
        return $token;
        return response()->json($return);

    }
    
    
    public function WebhookResponse(Request $request)
    {
        $response = $request->all();
        
        $CheckPaymentReference = $response->paymentReference;
        
        // if();
        
        $userExpense = userExpense::where('paymentReference', $response->paymentReference)->update([
            'payThruReference' => $response->payThruReference,
            'fiName' => $response->fiName,
            'status' => $response->status,
            'amount' => $response->amount,
            'merchantReference' => $response->merchantReference,
            'paymentMethod' => $response->paymentMethod,
            'commission' => $response->commission,
            'residualAmount' => $response->residualAmount,
            'resultCode' => $response->resultCode,
            'responseDescription' => $response->responseDescription,
        ]);
          // Log::info("webhook-data" . json_encode($response));
        
    
       return response()->json("", 200);
    }


    
}
