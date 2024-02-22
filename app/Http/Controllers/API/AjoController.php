<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\ReferralSetting;
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

class AjoController extends Controller
{
    //

    public $paythruService;
    public $referral;
    public $paymentLinkService;

    public function __construct(PaythruService $paythruService, PaymentLinkService $paymentLinkService, Referrals $referral)
    {
        $this->paythruService = $paythruService;
        $this->paymentLinkService = $paymentLinkService;
        $this->referral = $referral;
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
        $acct = $request->input('account_number');
        $bank = Bank::where('user_id', auth()->user()->id)
            ->where('account_number', $acct)
            ->first();

        if (!$bank) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

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



public function inviteUserToAjo(Request $request, $ajoId)
{
  //  $this->paymentLinkService->sendPaymentLinkToUsers();
    $ajo = Ajo::findOrFail($ajoId);
    $permittedMember = $ajo->member_count;
    $startingDate = $ajo->starting_date;
    $frequency = $ajo->frequency;
    $limiter = Invitation::where('inviter_id', auth()->user()->id)->where('ajo_id', $ajo->id)->count();
    $users = $request->input('users');

    if ($limiter >= $permittedMember) {
        return response()->json(['message' => 'Members cannot be more than ' . $permittedMember], 400);
    }

    $inviteLinks = [];
    $commonUniqueCode = Str::random(10);
    $cycleMultiplier = 1;

    if (!empty($users)) {
        $users = is_array($users) ? $users : [$users];

        foreach ($users as $user) {
            if (isset($user['email'])) {
                $existingUser = User::where('email', $user['email'])->first();

                // Create a new invitation for the user
                $invitation = new Invitation();
                $invitation->email = $user['email'];
                $invitation->inviter_id = auth()->id();
                $invitation->ajo_id = $ajo->id;
                $invitation->amount = $ajo->amount_per_member;
                $invitation->token = Str::random(60);

                // Check if the user exists
                if ($existingUser) {
                    $invitation->position = $user['position'] ?? $existingUser->position;
                    $invitation->name = $existingUser->name;
                    $invitation->phone_number = $existingUser->phone_number;
                } else {
                    $name = $user['name'] ?? '';
                    $phoneNumber = $user['phone_number'] ?? '';

                    $invitation->position = $user['position'] ?? '';
                    $invitation->name = $name;
                    $invitation->phone_number = $phoneNumber;
                }

                $invitation->save();

                // Calculate the next payment dates based on starting date and frequency for this user
                $nextPaymentDates = [];
                $paymentDate = $startingDate; // Initial payment date

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
                        default:
                            break;
                    }
                }

                // Insert payment dates into the payment_dates table for this user
                foreach ($nextPaymentDates as $index => $paymentDate) {
                    $paymentData = new PaymentDate();
                    $paymentData->invitation_id = $invitation->id;
                    $paymentData->payment_date = $paymentDate;

                    // Calculate the collection date based on position
                    $position = $user['position'] ?? null;

                    switch ($frequency) {
    case 'Daily':
        $collectionDate = date('Y-m-d', strtotime("-1 day", strtotime("+$position days", strtotime($startingDate))));
        break;
    case 'Weekly':
        $collectionDate = date('Y-m-d', strtotime("-7 days", strtotime("+$position weeks", strtotime($startingDate))));
        break;
    case 'Monthly':
        $collectionDate = date('Y-m-d', strtotime("-1 month", strtotime("+$position months", strtotime($startingDate))));
        break;
    case 'Quarterly':
        $collectionDate = date('Y-m-d', strtotime("-3 months", strtotime("+$position months", strtotime($startingDate))));
        break;
    default:
        break;
}


                    $paymentData->position = $invitation->position;
                    $paymentData->collection_date = $collectionDate;
                    $paymentData->save();
                }

                if ($existingUser) {
                    $inviteLink = 'https://www.azatme.com/login?invitee_name=' . $existingUser->name . '&email=' . $existingUser->email . '&inviter_token=' . $invitation->token . '&position=' . $invitation->position;
                } else {
                    $inviteLink = 'https://www.azatme.com/register?invitee_name=' . $invitation->name . '&email=' . $invitation->email . '&phone_number=' . $invitation->phone_number . '&position=' . $invitation->position . '&inviter_token=' . $invitation->token;
                }

                $inviteLinks[] = $inviteLink;

                Mail::to($user['email'])->send(new MyEmail($inviteLink, $invitation->name, $nextPaymentDates, $collectionDate));
            }
        }

        return response(["status" => 200, "message" => "Invitations sent successfully"]);
    } else {
        return response(["status" => 400, "message" => "Invalid user data"]);
    }
}


public function acceptInvitation(Request $request)
{
    //$this->paymentLinkService->sendPaymentLinkToUsers();
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
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType);
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
//        $activeOpenPayment = new OpenActive([
  //          'transactionReference' => $data->transactionDetails->merchantReference,
    //        'product_id' => $productId,
      //      'product_type' => $modelType
      //  ]);
       // $activeOpenPayment->save();

        Log::info("Ajo Contributor saved in Contributor table");
        Log::info("Invitation updated");

}
        } elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received ajo withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = AjoWithdrawal::where('transactionReference', $transactionReferences)->first();
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType);
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

        //$latestWithdrawal = AjoBalanace::where('user_id', auth()->user()->id)
          //  ->latest()
           // ->pluck('balance')
           // ->first();
	$AjoBalance = AjoWithdrawal::where('beneficiary_id', Auth::user()->id)->whereNotNull('status')->sum('amount');
   	$getAjoTransactions = Invitation::where('email', Auth::user()->email)->sum('residualAmount');
    	$AjoTransactions = $getAjoTransactions - $AjoBalance;

	if ($requestAmount < 130) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

        if ($AjoTransactions) {
            if ($requestAmount > $AjoTransactions) {
                return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
            }
        $minusResidual = $AjoTransactions - $requestAmount;
	}
        $refundmeAmountWithdrawn = $requestAmount - $charges;
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



public function sendPaymentLinkToUsers()
{
    $productId = env('PayThru_ajo_productid');
    $secret = env('PayThru_App_Secret');
    $owners = PaymentDate::whereDate('collection_date', now()->toDateString())->get();

    foreach ($owners as $owner) {
        $ajoBenefit = Invitation::where('id', $owner->invitation_id)->first();
        $ajoIds = $ajoBenefit->ajo_id;
        $ben = $ajoBenefit->name;
        $today = Carbon::today();
        $users = PaymentDate::whereDate('payment_date', $today->toDateString())
            ->with('invitation')
            ->whereHas('invitation', function ($query) use ($ajoIds) {
                $query->where('ajo_id', $ajoIds);
            })
            ->get();

        // Generate the payment link once for the AJO and collection date
        $token = $this->paythruService->handle();
       	$amount = $users->first()->invitation->amount;

        $ajoId = $users->first()->invitation->ajo_id;
        $ajoN = Ajo::where('id', $ajoId)->first();
        $ajoName = $ajoN->name;
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
$day = $today->toDateString();

            $emailAlreadySent = AjopaymentSent::where([
                'email' => $email,
                'ajo_id' => $ajoId,
                'status' => 1,
                'date_sent' => now()->toDateString(),
            ])->first();

            if (!$emailAlreadySent && ($status === null || $status === 'decline')) {
                // Log that the user has not accepted the invitation
                Log::info('Payment link not sent to ' . $email . ' as the user has not accepted the AJO invitation.');
            }
            elseif (!$emailAlreadySent) {
                $getLastString = (explode('/', $payLink));
                $now = end($getLastString);

                Invitation::where([
                    'email' => $ajoBenefit->email,
                    'ajo_id' => $ajoId,
                    'token' => $ajoBenefit->token,
                ])->update([
                    'paymentReference' => $now,
                    'merchantReference' => $paymentLinks['transId'],
                ]);

                $auth = Auth::user()->name;
                $this->sendPaymentLinkByEmail($email, $payLink, $ajoName, $auth, $day, $ben);
                // Insert the record into AjopaymentSent table with status 1
                $this->markPaymentLinkAsSent($email, $ajoId);
                Log::info('Payment link sent to ' . $email);
            } else {
                Log::info('Email already sent for AJO ID ' . $ajoId . ' and email ' . $email);
            }
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
