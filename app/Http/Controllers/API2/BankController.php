<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BankRequest;
use App\Bank;
use Auth;
use App\Setting;
use Illuminate\Support\Facades\Http;
use App\Services\PaythruService;

class BankController extends Controller
{
    //

  public $paythruService;

  public function __construct(PaythruService $paythruService)
  {
      $this->paythruService = $paythruService;
  }

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
    $prodUrl = env('PayThru_Base_Live_Url');
    $token = $this->paythruService->handle();

    if (!$token) {
        return "Token retrieval failed";
    } elseif (is_string($token) && strpos($token, '403') !== false) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
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
