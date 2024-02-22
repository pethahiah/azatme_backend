<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentLinkMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\PaymentDate;
use App\Services\PaythruService;
use App\AjopaymentSent;
use Auth;
use App\Ajo;
use App\Invitation;

class PaymentLinkService
{
    public $paythruService;

    public function __construct(PaythruService $paythruService)
    {
        $this->paythruService = $paythruService;
    }

    public function sendPaymentLinkToUsersssss()
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

            // Generate the payment link once for the AJO
            $payLink = null;
            $token = $this->paythruService->handle();

            foreach ($users as $user) {
                // Use the details of the first user within the AJO to generate the link
                if ($payLink === null) {
                    $amount = $user->invitation->amount;
                    $ajoId = $user->invitation->ajo_id;

                    $ajoN = Ajo::where('id', $ajoId)->first();
                    $ajoName = $ajoN->name;
                    $paymentLinks = $this->generatePaymentLink($amount, $ajoId, $token, $secret, $productId);
                    if (!$paymentLinks) {
                        return response()->json([
                            'error' => 'Failed to generate payment link for AJO ID ' . $ajoId
                        ], 500);
                    }
                }
                $email = $user->invitation->email;
                $day = $today->toDateString();
                // Check if the user has already received an email with the same ajoId, status is 1, and the date is the current date
                $emailAlreadySent = AjopaymentSent::where([
                    'email' => $email,
                    'ajo_id' => $ajoId,
                    'status' => 1,
                    'date_sent' => now()->toDateString(),
                ])->first();
		$payLink = $paymentLinks['paymentLink'];

                if (!$emailAlreadySent) {
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
        //$amount = $users->first()->invitation->amount;
	$firstUser = $users->first();

	if ($firstUser && is_object($firstUser->invitation)) {
    	// Case where $firstUser->invitation is an object
    	$amount = $firstUser->invitation->amount;
	} elseif ($firstUser && is_array($firstUser->invitation) && !empty($firstUser->invitation)) {
    	// Case where $firstUser->invitation is an array
    	$amount = reset($firstUser->invitation)['amount'];
	} else {
    	// Handle the case where $firstUser or $firstUser->invitation is not as expected
    	return response()->json([
        'error' => 'Invalid user or invitation data'
    	], 500);
	}

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

