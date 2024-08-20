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

    /**
     * @group Bank Management
     *
     * Add a new bank account to the user's profile.
     *
     * This endpoint allows the user to add a bank account by providing necessary details like bank name, account name, bank code, account number, and a reference ID. The method also checks if the account number already exists to prevent duplicates.
     *
     * @bodyParam name string required The name of the bank. Example: "First Bank"
     * @bodyParam account_name string required The name associated with the bank account. Example: "John Doe"
     * @bodyParam bankCode string required The code of the bank. Example: "011"
     * @bodyParam account_number string required The account number for the bank account. Example: "1234567890"
     * @bodyParam referenceId string optional A reference ID associated with this bank account. Example: "REF123456"
     *
     * @response 200 {
     *     "success": true,
     *     "bank": {
     *         "id": 1,
     *         "user_id": 3,
     *         "bankName": "First Bank",
     *         "account_name": "John Doe",
     *         "bankCode": "011",
     *         "account_number": "1234567890",
     *         "referenceId": "REF123456",
     *         "created_at": "2024-08-18T14:00:00.000000Z",
     *         "updated_at": "2024-08-18T14:00:00.000000Z"
     *     }
     * }
     *
     * @response 409 {
     *     "message": "Account number already exists"
     * }
     *
     * @response 422 {
     *     "message": "Validation failed",
     *     "errors": {
     *         "name": [
     *             "The name field is required."
     *         ],
     *         "account_name": [
     *             "The account name field is required."
     *         ]
     *         // Other validation errors
     *     }
     * }
     * @post /addBank
     */

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

    /**
     * @group Bank Management
     *
     * Get all bank accounts for the authenticated user.
     *
     * This endpoint retrieves all bank accounts that have been added by the authenticated user. It returns a list of all bank accounts associated with the user's ID.
     *
     * @response 200 {
     *     "banks": [
     *         {
     *             "id": 1,
     *             "user_id": 3,
     *             "bankName": "First Bank",
     *             "account_name": "John Doe",
     *             "bankCode": "011",
     *             "account_number": "1234567890",
     *             "referenceId": "REF123456",
     *             "created_at": "2024-08-18T14:00:00.000000Z",
     *             "updated_at": "2024-08-18T14:00:00.000000Z"
     *         },
     *         {
     *             "id": 2,
     *             "user_id": 3,
     *             "bankName": "GTBank",
     *             "account_name": "Jane Doe",
     *             "bankCode": "058",
     *             "account_number": "0987654321",
     *             "referenceId": "REF654321",
     *             "created_at": "2024-08-18T15:00:00.000000Z",
     *             "updated_at": "2024-08-18T15:00:00.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 401 {
     *     "error": "Unauthorized"
     * }
     * @get /getBankPerUser
     */

    public function getBankPerUser()
    {
    $user = Auth::user();
    $getBankPerUser = Bank::where('user_id', $user->id)->get();
        return response()->json($getBankPerUser);
    }

    /**
     * @group Bank Management
     *
     * Get all banks.
     *
     * This endpoint retrieves a list of all banks stored in the database, including their details like bank name, account name, account number, and associated user ID.
     *
     * @response 200 {
     *     "banks": [
     *         {
     *             "id": 1,
     *             "user_id": 3,
     *             "bankName": "First Bank",
     *             "account_name": "John Doe",
     *             "bankCode": "011",
     *             "account_number": "1234567890",
     *             "referenceId": "REF123456",
     *             "created_at": "2024-08-18T14:00:00.000000Z",
     *             "updated_at": "2024-08-18T14:00:00.000000Z"
     *         },
     *         {
     *             "id": 2,
     *             "user_id": 4,
     *             "bankName": "GTBank",
     *             "account_name": "Jane Doe",
     *             "bankCode": "058",
     *             "account_number": "0987654321",
     *             "referenceId": "REF654321",
     *             "created_at": "2024-08-18T15:00:00.000000Z",
     *             "updated_at": "2024-08-18T15:00:00.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 401 {
     *     "error": "Unauthorized"
     * }
     */


    public function getAllBanks()
    {
        $getAllBanks = Bank::all();
        return response()->json($getAllBanks);
    }

    /**
     * @group Bank Management
     *
     * Update a bank.
     *
     * This endpoint allows the user to update the details of a specific bank account by providing the bank's ID. Only certain fields like `bankName` can be updated.
     *
     * @urlParam bankid int required The ID of the bank to be updated. Example: 1
     *
     * @bodyParam bankName string required The name of the bank. Example: "Zenith Bank"
     * @bodyParam account_name string The name associated with the account. Example: "John Doe"
     * @bodyParam bankCode string The bank's unique code. Example: "057"
     * @bodyParam account_number string The account number associated with the bank. Example: "1234567890"
     * @bodyParam referenceId string A unique reference ID for the bank account. Example: "REF123456"
     *
     * @response 200 {
     *     "id": 1,
     *     "user_id": 3,
     *     "bankName": "Zenith Bank",
     *     "account_name": "John Doe",
     *     "bankCode": "057",
     *     "account_number": "1234567890",
     *     "referenceId": "REF123456",
     *     "created_at": "2024-08-18T14:00:00.000000Z",
     *     "updated_at": "2024-08-18T14:05:00.000000Z"
     * }
     *
     * @response 404 {
     *     "message": "Bank not found"
     * }
     *
     * @response 422 {
     *     "message": "Validation Error",
     *     "errors": {
     *         "bankName": ["The bankName field is required."],
     *         "account_number": ["The account_number field is required."]
     *     }
     * }
     *
     * @response 401 {
     *     "error": "Unauthorized"
     * }
     * @put /updateBank/{bankid}
     */
    public function updateBank(Request $request, $bankid)
    {
        //return response($request->all());
        $update = Bank::find($bankid);
         $update->bankName=$request->input('bankName');

        // $update->account_name=$request->input('account_name');
        $update->update($request->all());
        return response()->json($update);

    }

    /**
     * @group Bank Management
     *
     * Delete a bank.
     *
     * This endpoint allows the user to delete a specific bank account by providing the bank's ID.
     *
     * @urlParam id int required The ID of the bank to be deleted. Example: 1
     *
     * @response 204 {
     *     "message": "Bank deleted successfully"
     * }
     *
     * @response 404 {
     *     "message": "Bank not found"
     * }
     *
     * @response 401 {
     *     "error": "Unauthorized"
     * }
     * @delete /bank/{id}
     */


    public function bank($id)
    {

    $deleteBank = Bank::findOrFail($id);
   // return $deleteBank;
    if($deleteBank)
       $deleteBank->delete();
    else
    return response()->json(null);
}

    /**
     * @group Bank Management
     *
     * Get NGN Banks List from External API
     *
     * This endpoint retrieves a list of Nigerian banks by making a request to an external API.
     * It requires a valid authorization token, which is handled internally.
     *
     * @response 200 {
     *    "banks": [
     *        {
     *            "bankName": "Access Bank",
     *            "bankCode": "044"
     *        },
     *        {
     *            "bankName": "Zenith Bank",
     *            "bankCode": "057"
     *        }
     *    ]
     * }
     *
     * @response 403 {
     *    "error": "Access denied. You do not have permission to access this resource."
     * }
     *
     * @response 500 {
     *    "error": "Failed to retrieve the list of banks."
     * }
     */
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
