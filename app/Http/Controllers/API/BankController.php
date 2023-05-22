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
     // return $token;
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


//++aaGQ9gNrFWXrxoNDvbMbxVvvNtfZtZf4ad2NCFSJkwfmA/fnNp7c0FZna6PnuAWJ1BOPG4yJxQSRTlUHeitfJxE7cQ0VNik2CZP1xkIN8rFKlusp8pKcXIYvt7WHJRaH7Zp/wFIUucl/7N4uagMbU0jnAY/DU+TDmRs81KtWXtblV+9quIr5goA4mJdJkBfkpe1EqrcxIv3CY8elZF0HKVi2F8h9etgkqufMaBiAG7p6QKK5cCt8p/dfoUEzlQJbWwTQJlGHlmmnZajD1ISvSZaYrTUaxrhBMnlgiu9w0PerYwQ7eEh9aFsLjOkjcut0NDpIwH4I+CqtFjy1DnNQghx+QL2rWw33E3S8RjlZT1s3mxrn6PnyaTKKV99M71Za7rBpXE3Wf/7ydR8+C/xkK8x9BvDlfLdDkNvzw+gpENyr1RPS9ulYk2ySSMp/Ubo4M7wZMtap2lFZIsl1OrqZ9NJSbV8ul1WkW5x6zAZOVPhrQri/lpO+uPMjBx0VAHXQO6cseiqkmQjHv1G/rvFbQGyYzCqX+XuOpNVk9LQ3ztX6KvdUS2kXa+ts57bac7bKlnGyPXSGjYFkENcbP/So6GY3+LtgrfUM5UxellaFWa5yPmwmoTsP7Vt4qRw5Vy+odbD1PI633hfUAuc/Qm4rRExbDWM2t3D8D+9oxFeYwftd4R3mBZDwmGGy9ZnnpAspKi1KK8EfduE6N7rsqcJOoG5f4JHIlLVQrlpXJz5E5YwVjkQsCxmg+FuO4KD4D2PPsgIVP3PLlUDPO4u8Nu0SLdx12ZocN+Y3y7tQMtJCpTmgPeHeBeOcoax+DTHCiQ3Q5VMdiWrNNdopnEWLfHRO1DFnjFlJOuAqznBnYbz0DMOowAi8WKJEy3QfMhbxXh7wU3/lApVgnuvmo08b/Sh5h95j9LkcuSArueBiV4sKB4cBmNPJYkYxeahnYa89rtgV7AnXECyD2FoGC+TFqvFlQudLUYtSImsMwtw9ZPZsT3S0GEuPgFiEARjZbGm0acUWMvq263mj2TqSQLAa6PEQe6lZ8sdaMRKkwas4hGHUz8/3+vy/lEoBPxwPZMzXBMI/P7xMI3bhuAfaQ3CGP6LPwIyJiN4hHAwg1QdtTQ/8K7QE2mub1yzye/kuIe1G1hNIZILv+vLJ0sLId17dr7L5GM34ylZJnHRptIW8g5uUefYgWgQ+hZ09SlMqWs5Sb3hLYEslYNbBKMDiTCWS/q6q2pYBO17kDu6JlQxosvY+XGkoJQJytE+mYoT83f/p4ZYIRQKElWND/w7Cguq25XogvdFN5HCLU

}
