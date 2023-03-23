<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Nqr;
use App\Setting;    



class NQRController extends Controller
{
    //
    public function NqrMerchantRegistration(Request $request)
    {
        
        $param = Setting::where('id', 1)->first();
        $token = $param->token;

        $data = [
            "name" => $request->name,
            "tin" => $request->tin,
            "contact" => $request->contact,
            "phone" => $request->phone,
            "email" => $request->email,
            "address" => $request->address,
            "bankNo" => $request->bankNo,
            "accountName" => $request->accountName,
            "accountNumber" => $request->accountNumber,
            "referenceCode" => $request->referenceCode,
            "remarks" => $request->remarks,
        ];
   // dd($data);
          $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
          ])->post('http://sandbox.paythru.ng/Nqr/Agg/Merchant/Register', $data);
          if($response->successful())
          //return $response;
            {   
              $ngrRegistration = json_decode($response->body(), true);
              return response()->json($ngrRegistration);
            }
}

}
