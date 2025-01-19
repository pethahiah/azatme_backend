<?php

namespace App\Http\Controllers\API;



use App\Charge;
use App\DirectDebitProduct;
use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ChargeService;
use App\Services\Referrals;
use Illuminate\Http\Request;
use App\Services\PaythruService;
use App\Services\PaymentLinkService;
use App\Invitation;
use App\Bank;
use App\Ajo;
use Mail;
use App\Decline;
use App\User;
use Auth;
use App\PaymentDate;
use App\AjoBalanace;
use App\OpenActive;
use App\AjoContributor;
use Illuminate\Support\Str;
use App\Mail\MyEmail;
use DB;
use App\Active;
use Illuminate\Support\Facades\Http;
use App\Mail\PaymentLinkMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\AjopaymentSent;
use App\AjoWithdrawal;
use Illuminate\Support\Facades\Validator;



class AjoController extends Controller
{
    //

    public $paymentLinkService;
    public $referral;
    public $chargeService;
    protected $apiKey;
    protected $apiUrl;




    public function __construct(PaythruService $paythruService, PaymentLinkService $paymentLinkService, Referrals $referral, ChargeService $chargeService)
    {
        $this->paythruService = $paythruService;
        $this->paymentLinkService = $paymentLinkService;
        $this->referral = $referral;
        $this->chargeService = $chargeService;
        $this->apiKey = env('PayThru_ApplicationId');
        $this->apiUrl = env('Paythru_Direct_Debt_Test_Url');
    }


public function getTransactionData(Request $request, $transactionReference, $email)
{
    $perPage = $request->input('per_page', 10);
    $check = Invitation::where('merchantReference', $transactionReference)
        ->where('email', $email)
        ->first();

    if ($check) {
        $data = Invitation::join('ajo_contributors', 'invitations.merchantReference', '=', 'ajo_contributors.transactionReference')
            ->where('ajo_contributors.transactionReference', $transactionReference)
            ->orderBy('ajo_contributors.created_at', 'desc')
             ->paginate($perPage, ['invitations.merchantReference','ajo_contributors.*']);
    } else {
        $data = AjoContributor::where('transactionReference', $transactionReference)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    return response()->json($data);
}


public function getAjoByIdd(Request $request, $ajoId)
{
//    $user = Auth::user()->email;
    $ajo = Invitation::where('ajo_id', $ajoId)->get();

    if ($ajo) {
        return response()->json(['message' => 'successful', 'data' => $ajo], 200);
    } else {
        return response()->json(['message' => 'data not found'], 404);
    }
}


public function getAjoContributors(Request $request, $ajo_id) {
    $perPage = $request->input('per_page', 10);

    // Retrieve all rows for the specified 'ajo_id'
    $check = Invitation::where('ajo_id', $ajo_id)->get();

    if ($check->count() > 0) {
        $ajoContributorsData = [];

        // Loop through each row
        foreach ($check as $invitation) {
            // Extract 'merchantReference' for the current row
            $merchantReference = $invitation->merchantReference;

            // Retrieve AjoContributors with the correct 'merchantReference'
            $ajoContributors = AjoContributor::where('transactionReference', $merchantReference)
                ->paginate($perPage)->toArray();

            // Extract email for the current row
            $email = $invitation->email;

            // Group AjoContributors by 'transactionReference' and include email
            $ajoContributorsData[$merchantReference] = [
                'transactionReference' => $merchantReference,
                'email' => $email,
                'ajoContributors' => $ajoContributors['data'],
            ];
        }

        return response([
            'data' => $ajoContributorsData,
            'message' => 'AjoContributors retrieved successfully',
        ], 200);
    } else {
        return response([
            'message' => 'No data found for the specified ajo_id',
        ], 404);
    }
}

public function createAjo(Request $request)
    {


        $ajo = Ajo::create([

            'name' => $request->input('name'),
            'account_number' => $request->input('account_number'),
            'description' => $request->input('description'),
            'unique_code' => Str::random(10),
            'frequency' => $request->input('frequency'),
            'member_count' => $request->input('member_count'),
            'starting_date' => $request->input('starting_date'),
            'cycle' => $request->input('cycle'),
            'amount_per_member' => $request->input('amount_per_member'),
            'user_id' => auth()->user()->id,
        ]);

        return response()->json($ajo);
    }


private function calculateNextPaymentDates($startingDate, $frequency, $permittedMember, $cycleMultiplier)
{
    $nextPaymentDates = [];
    $paymentDate = $startingDate;

    for ($i = 0; $i < $permittedMember; $i++) {
        $nextPaymentDates[] = $paymentDate;

        // Update paymentDate based on the request frequency
        switch ($frequency) {
            case 'Daily':
                $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier days", strtotime($paymentDate)));
                break;
            case 'Weekly':
                $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier weeks", strtotime($paymentDate)));
                break;
            case 'Monthly':
                $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier months", strtotime($paymentDate)));
                break;
            case 'Quarterly':
                $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier months", strtotime($paymentDate)));
                break;
        }
    }

    return $nextPaymentDates;
}



private function calculateCollectionDate($startingDate, $frequency, $position)
{
    switch ($frequency) {
        case 'Daily':
            return date('Y-m-d', strtotime("-1 day", strtotime("+$position days", strtotime($startingDate))));
        case 'Weekly':
            return date('Y-m-d', strtotime("-7 days", strtotime("+$position weeks", strtotime($startingDate))));
        case 'Monthly':
            return date('Y-m-d', strtotime("-1 month", strtotime("+$position months", strtotime($startingDate))));
        case 'Quarterly':
            return date('Y-m-d', strtotime("-3 months", strtotime("+$position months", strtotime($startingDate))));
        default:
            return null;
    }
}



// private function addProduct(array $requestData): array
// {
//     try {
//         // Validate the incoming request data
//         $validatedData = validator($requestData, [
//             'productName' => 'required|string',
//             'productDescription' => 'required|string',
//         ])->validate();

//         // Return validated data as array
//         return [
//             'productName' => $validatedData['productName'],
//             'productDescription' => $validatedData['productDescription'],
//         ];
//     } catch (\Exception $e) {
//         Log::error('Error validating product data: ' . $e->getMessage());
//         throw new \Exception('Failed to add product. Please try again later.');
//     }
// }

// public function inviteUserToAjo(Request $request, $ajoId, $requestData)
// {
//     $ajo = Ajo::findOrFail($ajoId);
//     $permittedMember = $ajo->member_count;
//     $startingDate = $ajo->starting_date;
//     $frequency = $ajo->frequency;
//     $limiter = Invitation::where('inviter_id', auth()->user()->id)->where('ajo_id', $ajo->id)->count();
//     $payload = $request->all();
    
//   // return $payload;


//     if ($limiter > $permittedMember) {
//         return response()->json(['message' => 'Members cannot be more than ' . $permittedMember], 400);
//     }
    
//     if (empty($payload['users'])) {
//         return response()->json(['message' => 'Invalid user data'], 400);
//     }

//     $users = $payload['users'];
//     $inviteLinks = [];
//     $cycleMultiplier = 1;
//     $authUserEmail = auth()->user()->email;


//     // Generate the product only once
//     try {
//         $productData = $this->addProduct($requestData);
//         $product = new DirectDebitProduct();
//         $product->productName = $productData['productName'];
//         $product->productDescription = $productData['productDescription'];
//         $product->isUserResponsibleForCharges = true;
//         $product->classification = "FixedContract";
//         $product->partialCollectionEnabled = false;
//         $product->user_id = Auth::user()->id;
//         $product->save();
        
//         // Prepare the payload for the API
//         $productPayload = [
//             'productName' => $product->productName,
//             'productDescription' => $product->productDescription,
//             'isUserResponsibleForCharges' => $product->isUserResponsibleForCharges,
//             'classification' => $product->classification,
//             'partialCollectionEnabled' => $product->partialCollectionEnabled,
//         ];

//         Log::info('Payload sent to gateway: ' . json_encode($productPayload));

//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'ApplicationId' => $this->apiKey,
//         ])->post($this->apiUrl . '/Product/create', $productPayload);

//         if ($response->successful()) {
//             $responseData = $response->json();
            
//             if ($responseData['succeed']) {
//                 $productId = $responseData['data']['productId'];
//                 $product->productId = (string)$productId;
//                 $product->save();
//                 Log::info('Response from paythru API: ' . $response->body());
//             } else {
//                 Log::error('Error in response from API: ' . $response->body());
//                 return response()->json(['message' => 'Error in response from API'], 500);
//             }
//         } else {
//             Log::error('API request failed: ' . $response->body());
//             return response()->json(['message' => 'API request failed'], 500);
//         }
//     } catch (\Exception $e) {
//         Log::error('Error creating DirectDebitProduct: ' . $e->getMessage());
//         return response()->json(['message' => 'Error creating DirectDebitProduct'], 500);
//     }

//     // Process each user with the single productId
//     foreach ($users as $user) {
//         if (!isset($user['email'])) {
//             continue;
//         }
        
//         $existingUser = User::where('email', $user['email'])->first();

//         // Create a new invitation for the user
//         $invitation = new Invitation();
//         $invitation->email = $user['email'];
//         $invitation->inviter_id = auth()->id();
//         $invitation->ajo_id = $ajo->id;
//         $invitation->amount = $ajo->amount_per_member;
//         $invitation->token = Str::random(6);
//         $invitation->position = $user['position'] ?? ($existingUser->position ?? '');
//         $invitation->name = $existingUser->name ?? ($user['name'] ?? '');
//         $invitation->phone_number = $existingUser->phone_number ?? ($user['phone_number'] ?? '');
//         $invitation->save();

//         // Calculate the next payment dates based on starting date and frequency for this user
//         $nextPaymentDates = $this->calculateNextPaymentDates($startingDate, $frequency, $permittedMember, $cycleMultiplier);

//         // Insert payment dates into the payment_dates table for this user
//         foreach ($nextPaymentDates as $paymentDate) {
//             $paymentData = new PaymentDate();
//             $paymentData->invitation_id = $invitation->id;
//             $paymentData->payment_date = $paymentDate;

//             $position = $user['position'] ?? null;
//             $collectionDate = $this->calculateCollectionDate($startingDate, $frequency, $position);
//             $paymentData->position = $invitation->position;
//             $paymentData->collection_date = $collectionDate;
//             $paymentData->save();
//         }


//         // Generate invite link using the single productId
//       $inviteLink = $existingUser
//     ? 'https://azatme-frontend.vercel.app/login?invitee_name=' . $existingUser->name . '&email=' . $existingUser->email . '&inviter_token=' . $invitation->token . '&position=' . $invitation->position . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName
//     : 'https://azatme-frontend.vercel.app/register?invitee_name=' . $invitation->name . '&email=' . $invitation->email . '&phone_number=' . $invitation->phone_number . '&position=' . $invitation->position . '&inviter_token=' . $invitation->token . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName;


//         $inviteLinks[] = $inviteLink;

//         // Send email if the invited user is not the authenticated user
//         if ($user['email'] !== $authUserEmail) {
//             Mail::to($user['email'])->send(new MyEmail(
//                 $invitation->name,
//                 $nextPaymentDates, 
//                 $collectionDate,
//                 $inviteLink
//             ));
//         }
//     }

//     return response()->json(['status' => 200, 'message' => 'Invitations sent successfully', 'links' => $inviteLinks]);
// }


private function addProduct(array $requestData): array
{
    try {
        // Validate the incoming request data
        $validatedData = Validator::make($requestData, [
            'productName' => 'required|string',
            'productDescription' => 'required|string',
        ])->validate();

        // Return validated data as an array
        return [
            'productName' => $validatedData['productName'],
            'productDescription' => $validatedData['productDescription'],
        ];
    } catch (\Exception $e) {
        Log::error('Error validating product data: ' . $e->getMessage());
        throw new \Exception('Failed to add product. Please try again later.');
    }
}

public function inviteUserToAjo(Request $request, $ajoId)
{
    $ajo = Ajo::findOrFail($ajoId);
    $permittedMember = $ajo->member_count;
    $startingDate = $ajo->starting_date;
    $frequency = $ajo->frequency;

    $limiter = Invitation::where('inviter_id', auth()->user()->id)->where('ajo_id', $ajo->id)->count();
    $payload = $request->all();

    if ($limiter >= $permittedMember) {
        return response()->json(['message' => 'Members cannot exceed ' . $permittedMember], 400);
    }

    if (empty($payload['users'])) {
        return response()->json(['message' => 'Invalid user data'], 400);
    }


    $users = $payload['users'];
    $inviteLinks = [];
    $cycleMultiplier = 1;
    $authUserEmail = auth()->user()->email;

    // Generate the product only once
    try {
        $productData = $this->addProduct($payload);
        $product = new DirectDebitProduct();
        $product->productName = $productData['productName'];
        $product->productDescription = $productData['productDescription'];
        $product->isUserResponsibleForCharges = true;
        $product->classification = "FixedContract";
        $product->partialCollectionEnabled = false;
        $product->user_id = Auth::user()->id;
        $product->save();

        // Prepare the payload for the API
        $productPayload = [
            'productName' => $product->productName,
            'productDescription' => $product->productDescription,
            'isUserResponsibleForCharges' => $product->isUserResponsibleForCharges,
            'classification' => $product->classification,
            'partialCollectionEnabled' => $product->partialCollectionEnabled,
        ];


        Log::info('Payload sent to gateway: ' . json_encode($productPayload));

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApplicationId' => $this->apiKey,
        ])->post($this->apiUrl . '/Product/create', $productPayload);
        
        Log::info('PayThru Gateway Response for create product: ' . $response);

        if ($response->successful()) {
            $responseData = $response->json();
            
            if ($responseData['succeed']) {
                $productId = $responseData['data']['productId'];
                $product->productId = (string)$productId;
                $product->save();
                Log::info('Response from paythru API: ' . $response->body());
            } else {
                Log::error('Error in response from API: ' . $response->body());
                return response()->json(['message' => 'Error in response from API'], 500);
            }
        } else {
            Log::error('API request failed: ' . $response->body());
            return response()->json(['message' => 'API request failed'], 500);
        }
    } catch (\Exception $e) {
        Log::error('Error creating DirectDebitProduct: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating DirectDebitProduct'], 500);
    }

    // Process each user with the single productId
    foreach ($users as $user) {
        if (!isset($user['email'])) {
            continue;
        }
        
        $existingUser = User::where('email', $user['email'])->first();

        // Create a new invitation for the user
        $invitation = new Invitation();
        $invitation->email = $user['email'];
        $invitation->inviter_id = auth()->id();
        $invitation->ajo_id = $ajo->id;
        $invitation->amount = $ajo->amount_per_member;
        $invitation->token = Str::random(6);
        $invitation->position = $user['position'] ?? ($existingUser->position ?? '');
        $invitation->name = $existingUser->name ?? ($user['name'] ?? '');
        $invitation->phone_number = $existingUser->phone_number ?? ($user['phone_number'] ?? '');
        $invitation->save();

        // Calculate the next payment dates based on starting date and frequency for this user
        $nextPaymentDates = $this->calculateNextPaymentDates($startingDate, $frequency, $permittedMember, $cycleMultiplier);

        // Insert payment dates into the payment_dates table for this user
        foreach ($nextPaymentDates as $paymentDate) {
            $paymentData = new PaymentDate();
            $paymentData->invitation_id = $invitation->id;
            $paymentData->payment_date = $paymentDate;

            $position = $user['position'] ?? null;
            $collectionDate = $this->calculateCollectionDate($startingDate, $frequency, $position);
            $paymentData->position = $invitation->position;
            $paymentData->collection_date = $collectionDate;
            $paymentData->save();
        }

        // Generate invite link using the single productId
        $inviteLink = $existingUser
            ? 'https://azatme-frontend.vercel.app/login?invitee_name=' . $existingUser->name . '&email=' . $existingUser->email . '&inviter_token=' . $invitation->token . '&position=' . $invitation->position . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName
            : 'https://azatme-frontend.vercel.app/register?invitee_name=' . $invitation->name . '&email=' . $invitation->email . '&phone_number=' . $invitation->phone_number . '&position=' . $invitation->position . '&inviter_token=' . $invitation->token . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName;

        $inviteLinks[] = $inviteLink;

        // Send email if the invited user is not the authenticated user
        if ($user['email'] !== $authUserEmail) {
            Mail::to($user['email'])->send(new MyEmail(
                $invitation->name,
                $nextPaymentDates, 
                $collectionDate,
                $inviteLink
            ));
        }
    }

    return response()->json(['status' => 200, 'message' => 'Invitations sent successfully', 'links' => $inviteLinks]);
}

public function acceptInvitation(Request $request)
{

    $inviteLink = $request->input('inviteLink');

    if (strpos($inviteLink, 'action=accept') !== false) {
        $query = parse_url($inviteLink, PHP_URL_QUERY);
        parse_str($query, $params);
        $inviterToken = $params['inviter_token'];

        $invitation = Invitation::where('token', $inviterToken)->first();

        if ($invitation) {
            // Update the status column to 'accept'
            $invitation->status = 'accept';
            $invitation->save();

            $accountNumber = $request->input('account_number');
            $bankName = $request->input('bank');
            $accountName = $request->input('account_name');
            $bankCode = $request->input('bankCode');

            // Check if the account number already exists in the Bank table
            $bank = Bank::where('account_number', $accountNumber)->first();

            if (!$bank) {
                // If the account number doesn't exist, create it in the Bank table
                $bank = Bank::create([
                    'account_number' => $accountNumber,
                    'bank_name' => $bankName,
                    'account_name' => $accountName,
                    'bankCode' => $bankCode,
                ]);
            }

            return response()->json(['message' => 'Invitation accepted successfully']);
        } else {
            return response()->json(['error' => 'Invalid invitation token'], 400);
        }
    } else {
        return response()->json(['error' => 'Invalid action'], 400);
    }
}




public function getAjoById(Request $request, $id)
{
    $perPage = $request->input('per_page', 10);

    // Validate the $id parameter to ensure it's a positive integer.
    if ($id){

    $getAjoById = Invitation::where('inviter_id', Auth::id())
        ->where('ajo_id', $id)
        ->latest()
        ->paginate($perPage);
    return response()->json($getAjoById);
} else {
            return response()->json(['error' => 'No Id found'], 400);
        }
}

public function declineInvitation(Request $request)
{

    $inviteLink = $request->input('inviteLink');


    if (strpos($inviteLink, 'action=decline') !== false) {
        // Extract the token from the inviteLink
        $query = parse_url($inviteLink, PHP_URL_QUERY);
        parse_str($query, $params);
        $inviterToken = $params['inviter_token'];

      $invitation = Invitation::where('token', $inviterToken)->first();

        if ($invitation) {
            // Update the status column to decline
            $invitation->status = 'decline';
            $invitation->save();

            // Create a Decline record if needed
            $ajo = Decline::create([
                'remark' => $request->input('remark'),
                'reason' => $request->input('reason'),
                'invitation_id' => $invitation->id,
                'user_id' => $invitation->inviter_id,
                'invitee_name' => $invitation->name,
            ]);
		return response()->json(['message' => 'Invitation declined successfully','Data' => $ajo]);
        } else {
            return response()->json(['error' => 'Invalid invitation token'], 400);
        }
    } else {
        return response()->json(['error' => 'Invalid action'], 400);
    }
}

public function getAllAjoCreatedPerUser(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $AuthUser = Auth::user()->id;
    $getAllAjoCreatedPerUser = Ajo::where('user_id', $AuthUser)->latest()->paginate($perPage);
    return response()->json($getAllAjoCreatedPerUser);
}

public function getAllAjoInvitationCreatedPerUser(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $getAuthUser = Auth::user();
   // $this->paymentLinkService->sendPaymentLinkToUsers();
    // Get the Ajo IDs where the user is the inviter
    $ajoIds = DB::table('ajos')
        ->join('invitations', function ($join) use ($getAuthUser) {
            $join->on('ajos.id', '=', 'invitations.ajo_id')
                ->where('ajos.user_id', '=', $getAuthUser->id);
        })
        ->select('ajos.id')
        ->groupBy('ajos.id')
        ->pluck('ajos.id');

    // Get user invitations with total paid amounts
    $getUserInvitation = DB::table('ajos')
        ->join('invitations', function ($join) {
            $join->on('ajos.id', '=', 'invitations.ajo_id');
        })
        ->select('ajos.id', 'ajos.name', 'ajos.description', 'ajos.starting_date', 'ajos.frequency', 'ajos.amount_per_member', 'ajos.cycle', 'ajos.member_count', DB::raw('SUM(invitations.residualAmount) as total_paid'))
        ->whereIn('ajos.id', $ajoIds)
        ->groupBy('ajos.id', 'ajos.name', 'ajos.description', 'ajos.starting_date', 'ajos.frequency', 'ajos.amount_per_member', 'ajos.cycle', 'ajos.member_count')
	->orderBy('ajos.created_at', 'desc')
        ->paginate($perPage);

    // Get invitations added by the user
    $getUserInvitationAddedTransactions = DB::table('invitations')
        ->join('ajos', 'invitations.ajo_id', '=', 'ajos.id')
        ->where('invitations.email', $getAuthUser->email)
        ->select(
            'invitations.*',
            'ajos.member_count as members',
            'ajos.starting_date as startDate',
            'ajos.frequency as freq',
            'ajos.amount_per_member as amount',
            'ajos.cycle as cycle',
            'ajos.name as Ajo_Name',
            'ajos.description as Descrip'
        )
	->orderBy('ajos.created_at', 'desc')
        ->paginate($perPage);

    return response()->json([
        'getAuthUserInvitationCreated' => $getUserInvitation,
        'getInvitationInvitedTo' => $getUserInvitationAddedTransactions,
    ]);
}


public function webhookAjoResponse(Request $request)
{
    try {
        $productId = env('PayThru_ajo_productid');
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
	$modelType = "Ajo";

	Log::info("Starting webhookAjoResponse", ['data' => $data, 'modelType' => $modelType]);
        if ($data->notificationType == 1) {
            if (is_null($data->transactionDetails->paymentReference)) {
         $invitation = Invitation::where('merchantReference', $data->transactionDetails->merchantReference)->first();

                $product_action = "payment";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType, $product_action);
                }


	if ($invitation) {
	$AjoContributor = new AjoContributor([
           'payThruReference' => $data->transactionDetails->payThruReference,
            'ajo_id' => $invitation->ajo_id,
            'transactionReference' => $data->transactionDetails->merchantReference,
            'fiName' => $data->transactionDetails->fiName,
            'status' => $data->transactionDetails->status,
            'amount' => $data->transactionDetails->amount,
            'responseCode' => $data->transactionDetails->resultCode ?? null,
            'paymentMethod' => $data->transactionDetails->paymentMethod,
            'commission' => $data->transactionDetails->commission,
            'residualAmount' => $data->transactionDetails->residualAmount,
            'responseDescription' => $data->transactionDetails->responseDescription ?? null,
            'providedEmail' => $data->transactionDetails->customerInfo->providedEmail,
            'providedName' => $data->transactionDetails->customerInfo->providedName,
            'remarks' => $data->transactionDetails->customerInfo->remarks ?? null,
        ]);
	$AjoContributor->save();
//	$invitation = Invitation::where('merchantReference', $data->transactionDetails->merchantReference)->first();
//	if ($invitation) {
	$invitation->residualAmount += $AjoContributor->residualAmount;
        $invitation->save();
}

        Log::info("Ajo Contributor saved in Contributor table");
        Log::info("Invitation updated");

}
        } elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received ajo withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = AjoWithdrawal::where('transactionReference', $transactionReferences)->first();
                $product_action = "withdrawal";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType, $product_action);
                }
                if ($withdrawal) {
                    $uniqueId = $withdrawal->uniqueId;

                    $updateAjoWithdrawal = AjoWithdrawal::where([
                        'transactionReference' => $transactionReferences,
                        'uniqueId' => $uniqueId
                    ])->first();

                    if ($updateAjoWithdrawal) {
                        $updateAjoWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
                        $updateAjoWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
                        $updateAjoWithdrawal->status = 'success';
                        $updateAjoWithdrawal->save();

                        Log::info("Ajo withdrawal updated");
                    } else {
                        Log::info("Ajo withdrawal not found for transaction references: " . $transactionReferences);
                    }
                } else {
                    Log::info("Withdrawal not found for transaction references: " . $transactionReferences);
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


public function getUsersWithBankInfo($ajo_id) {
    // Enable query logging
    DB::enableQueryLog();

    // Build and execute the query
    $invitations = DB::table('invitations')
        ->select('invitations.*', 'banks.*')
        ->join('users', 'invitations.email', '=', 'users.email')
        ->join('banks', 'users.id', '=', 'banks.user_id')
        ->where('invitations.ajo_id', $ajo_id)
        ->get();

   // $queries = DB::getQueryLog();
   return response()->json(['message' => 'successfully','Data' => $invitations]);
   // return ['invitations' => $invitations, 'queries' => $queries];
}




public function getUnpaidAjoUsers($id)
{
    $unpaidUsers = Invitation::where('ajo_id', $id)
        ->where('status', 'accept')
        ->where('residualAmount', null)
        ->get();

    $nowPlusOneDay = now()->addDay();

    $unpaidUsers = $unpaidUsers->filter(function ($invite) use ($nowPlusOneDay) {
        $paymentDate = PaymentDate::where('invitation_id', $invite->invitation_id)->first();

        return $paymentDate && $paymentDate->payment_date > $nowPlusOneDay;
    });

    return $unpaidUsers;
}

public function AjoPayout(Request $request)
    {
        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);
        $secret = env('PayThru_App_Secret');
        $productId = env('PayThru_ajo_productid');
        $hash = hash('sha512', $timestamp . $secret);
        $AppId = env('PayThru_ApplicationId');
        $prodUrl = env('PayThru_Base_Live_Url');
        $charges = env('PayThru_Withdrawal_Charges');

        $requestAmount = $request->amount;


        $latestCharge = Charge::orderBy('updated_at', 'desc')->first();
        $applyCharges = $this->chargeService->applyCharges($latestCharge);


        $requestAmount = $request->amount;
	$AjoBalance = AjoWithdrawal::where('beneficiary_id', Auth::user()->id)->whereNotNull('status')->sum('amount');
   	$getAjoTransactions = Invitation::where('email', Auth::user()->email)->sum('residualAmount');
    	$AjoTransactions = $getAjoTransactions - $AjoBalance;

	if ($requestAmount < 100) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

        if ($AjoTransactions) {
            if ($requestAmount > $AjoTransactions) {
                return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
            }
        $minusResidual = $AjoTransactions - $requestAmount;
	}
        $refundmeAmountWithdrawn = $requestAmount - $latestCharge->charges;
        $acct = $request->account_number;

        $bank = Bank::where('account_number', $acct)
            ->first();

        if (!$bank) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        $beneficiaryReferenceId = $bank->referenceId;
	    $benefit = $bank->user_id;
        $token = $this->paythruService->handle();

        if (!$token) {
            return "Token retrieval failed";
        } elseif (is_string($token) && strpos($token, '403') !== false) {
            return response()->json([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ], 403);
        }

        $data = [
            'productId' => $productId,
            'amount' => $refundmeAmountWithdrawn,
        'beneficiary' => [
                'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];

        $url = $prodUrl . '/transaction/settlement';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Payout request failed'], 500);
        }


       // Invitation::where('email', auth()->user()->email)->where('stat', 1)
         //   ->latest()->update(['minus_residual' => $minusResidual]);

	 AjoBalanace::create([
        	'user_id' => Auth::user()->id,
        	'balance' => $minusResidual,
		'action' => 'debit',
    	]);


        // Save the withdrawal details
            $withdrawal = new AjoWithdrawal([
            'accountNumber' => $request->accountNumber,
            'description' => $request->description,
            'beneficiary_id' => $benefit,
            'amount' => $requestAmount - $charges,
            'bank' => $request->bank,
            'charges' => $charges,
            'uniqueId' => Str::random(10),
        ]);
	 AjoBalanace::create([
        	'user_id' => Auth::user()->id,
        	'balance' => $minusResidual,
		    'action' => 'debit',
    	]);

        if ($applyCharges) {
            // Save the withdrawal details with charges
            $withdrawal = new AjoWithdrawal([
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
            $withdrawal = new AjoWithdrawal([
                'account_number' => $request->account_number,
                'description' => $request->description,
                'beneficiary_id' => auth()->user()->id,
                'amount' => $requestAmount,
                'bank' => $request->bank,
                'uniqueId' => Str::random(10),
            ]);
        }

        $withdrawal->save();

        $collection = $response->json();

        Log::info('API response: ' . json_encode($collection));
        $saveTransactionReference = AjoWithdrawal::where('uniqueId', $withdrawal->uniqueId)
            ->update([
                'transactionReference' => $collection['transactionReference'],
                'status' => $collection['message'],

            ]);

        return response()->json($saveTransactionReference, 200);
    }

public function getAjoWithdrawalTransaction(Request $request)
{
    //$this->paymentLinkService->sendPaymentLinkToUsers();
    $perPage = $request->input('per_page', 10);

    $getWithdrawalTransaction = AjoWithdrawal::where('beneficiary_id', auth()->user()->id)->paginate($perPage);

    if ($getWithdrawalTransaction->count() > 0) {
        return response()->json($getWithdrawalTransaction);
    } else {
        return response([
            'message' => 'Transaction not found for this user'

        ], 404);
   }
}


public function isPaylink($ajoId)
{
    // Log the start of the manual payment process for the given ajoId
    Log::info('Initiating manual payment approval', ['ajoId' => $ajoId, 'user_email' => Auth::user()->email]);

    try {
        // Attempt to find the ajo by the given ajoId first
        $ajo = Ajo::find($ajoId); 

        // If the ajo is not found, log and return a not found response
        if (!$ajo) {
            Log::warning('Ajo ID not found', ['ajoId' => $ajoId]);

            return [
                'message' => 'Ajo ID not found',
                'status'  => 404
            ];
        }


        // Find the invitation by the authenticated user's email and the given ajoId
        $getUser = Invitation::where('email', Auth::user()->email)
                             ->where('ajo_id', $ajoId)
                             ->first();

        // If the invitation exists, update the 'is_payLink' and 'status' fields
        if ($getUser) {
            $getUser->update([
                'is_payLink' => 1,
                'status'     => 'accept'
            ]);

            // Log the successful update
            Log::info('Manual payment approved', ['ajoId' => $ajoId, 'user_email' => Auth::user()->email]);

            // Return a success response
            return [
                'message' => 'Manual payment approved',
                'status'  => 200
            ];
        }

        // Log when the invitation is not found
        Log::warning('Invitation not found', ['ajoId' => $ajoId, 'user_email' => Auth::user()->email]);

        // Return a not found response
        return [
            'message' => 'User not found',
            'status'  => 404
        ];
    } catch (\Exception $e) {
        // Log any exception that occurs during the process
        Log::error('Error approving manual payment', [
            'ajoId'      => $ajoId,
            'user_email' => Auth::user()->email,
            'error'      => $e->getMessage()
        ]);

        // Return an error response
        return [
            'message' => 'An error occurred during manual payment approval',
            'status'  => 500
        ];
    }
}



public function sendPaymentLinkToUsers()
{
    $productId = env('PayThru_ajo_productid');
    $secret = env('PayThru_App_Secret');
    
    $owners = PaymentDate::whereDate('collection_date', now()->toDateString())->get();

    foreach ($owners as $owner) {
        $ajoBenefit = Invitation::find($owner->invitation_id);
        
        if (!$ajoBenefit || $ajoBenefit->is_payLink == 0) {
            return response()->json([
                'error' => 'You have not been permitted to manually send paylink to users.'
            ], 403);
        }

        $ajoId = $ajoBenefit->ajo_id;
        $today = Carbon::today();
        $users = PaymentDate::whereDate('payment_date', $today->toDateString())
            ->whereHas('invitation', function ($query) use ($ajoId) {  
                $query->where('ajo_id', $ajoId);
            })
            ->with('invitation')
            ->get();

        // Generate the payment link once for the AJO and collection date
        $token = $this->paythruService->handle();
        $amount = $users->first()->invitation->amount;
        $ajoName = Ajo::find($ajoId)->name;
        $paymentLinks = $this->generatePaymentLink($amount, $ajoId, $token, $secret, $productId);

        if (!$paymentLinks) {
            return response()->json([
                'error' => 'Failed to generate payment link for AJO ID ' . $ajoId
            ], 500);
        }

        $payLink = $paymentLinks['paymentLink'];

        foreach ($users as $user) {
            $email = $user->invitation->email;
            $status = $user->invitation->status;

            $emailAlreadySent = AjopaymentSent::where([
                'email' => $email,
                'ajo_id' => $ajoId,
                'status' => 1,
                'date_sent' => now()->toDateString(),
            ])->exists();

            if ($emailAlreadySent) {
                Log::info('Email already sent for AJO ID ' . $ajoId . ' and email ' . $email);
                continue;
            }

            if (is_null($status) || $status === 'decline') {
                Log::info('Payment link not sent to ' . $email . ' as the user has not accepted the AJO invitation.');
                continue;
            }

            $reference = basename($payLink);

            Invitation::where([
                'email' => $ajoBenefit->email,
                'ajo_id' => $ajoId,
                'token' => $ajoBenefit->token,
            ])->update([
                'paymentReference' => $reference,
                'merchantReference' => $paymentLinks['transId'],
            ]);

            $auth = Auth::user()->name;
            $this->sendPaymentLinkByEmail($email, $payLink, $ajoName, $auth, $today->toDateString(), $ajoBenefit->name);
            $this->markPaymentLinkAsSent($email, $ajoId);

            Log::info('Payment link sent to ' . $email);
        }
    }
}



    private function generatePaymentLink($amount, $ajoId, $token, $secret, $productId)
    {
        $hashSign = hash('sha512', $amount . $secret);
        $prodUrl = env('PayThru_Base_Live_Url');

        if (!$token) {
            return null;
        }

        $data = [
            'amount' => $amount,
            'productId' => $productId,
            'transactionReference' => time() . $ajoId,
            'paymentDescription' => $token,
            'paymentType' => 2,
            'sign' => $hashSign,
            'displaySummary' => true,
        ];

        $url = $prodUrl . '/transaction/create';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Failed to generate payment link');
            return null;
        }

    $transaction = json_decode($response->body(), true);
    $transId = $data['transactionReference'];
    $paymentLink = $transaction['payLink'];

    return compact('transId', 'paymentLink');
    }

    private function sendPaymentLinkByEmail($email, $payLink, $ajoName, $auth, $day, $ben)
    {
        if ($payLink) {
            Mail::to($email)->send(new PaymentLinkMail($payLink, $ajoName, $auth, $day, $ben));
        }
    }

    private function markPaymentLinkAsSent($email, $ajoId)
    {
        AjopaymentSent::insert([
            'email' => $email,
            'status' => 1,
            'date_sent' => now()->toDateString(),
            'ajo_id' => $ajoId,
        ]);
    }
    
    
    




}
