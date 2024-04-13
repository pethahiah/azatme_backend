<?php

namespace App\Services;

class DirectDebitService
{
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
}
