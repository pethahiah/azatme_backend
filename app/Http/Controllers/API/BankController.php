<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BankRequest;
use App\Bank;
use Auth;
use App\Setting;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
    //

    public function addBank(BankRequest $request){
    $bank = new Bank();
    $bank->bankName=$request->input('name');
    $bank->account_name=$request->input('account_name');
    $bank->bankCode=$request->input('bankCode');
    $bank->user_id = $request->user()->id;
    $bank ->account_number=$request->input('account_number');
    $bank ->referenceId=$request->input('referenceId');

     $checkBankName = Bank::where('account_number', $request->account_number)->get();
        if(sizeof($checkBankName) > 0){
            // tell user not to duplicate same bank name
            return response([
                'message' => 'Account number already exists'
            ], 409);
        }
        
    $bank -> save();
    return response()->json(['success' => true, $bank]);
    }

    public function getBankPerUser()
    {
    $user = Auth::user();
    $getBankPerUser = Bank::where('user_id', $user->id)->get();
        return response()->json($getBankPerUser);
    }


    public function getAllBanks()
    {
        $getAllBanks = Bank::all();
        return response()->json($getAllBanks);
    }

    public function updateBank(Request $request, $bankid)
    {
        //return response($request->all());
        $update = Bank::find($bankid);
         $update->bankName=$request->input('bankName');
         
        // $update->account_name=$request->input('account_name');
        $update->update($request->all());
        return response()->json($update);
    
    }


    public function bank($id) 
    {
        
    $deleteBank = Bank::findOrFail($id);
   // return $deleteBank;  
    if($deleteBank)
       $deleteBank->delete(); 
    else
    return response()->json(null); 
}


public function ngnBanksApiList()
{
    $current_timestamp= now();
    $timestamp = strtotime($current_timestamp);
   // echo $timestamp;
    $secret = env('PayThru_App_Secret');
    $hash = hash('sha256', $secret . $timestamp);
    $PayThru_AppId = env('PayThru_ApplicationId');
    $prodUrl = env('PayThru_Base_Live_Url');
    
    $data = [
      'ApplicationId' => $PayThru_AppId,
      'password' => $hash
    ];
    //return $data;
  $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Timestamp' => $timestamp,
])->post('https://services.paythru.ng/identity/auth/login', $data);
  //return $response;
  if($response->Successful())
  {
    $access = $response->object();
    $accesss = $access->data;
    $paythru = "Paythru";

    $token = $paythru." ".$accesss;

      
     $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $token,
  ])->get($prodUrl.'/bankinfo/listBanks');
    //return $response;
    if($response->Successful())
    {
      $banks = json_decode($response->body(), true);
      return response()->json($banks);
    }  
}

}



}
