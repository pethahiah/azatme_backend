<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\PaymentDate;
use App\Mail\PaymentLinkMail;
use Illuminate\Http\Request;
use App\Services\PaythruService;
use App\Invitation;
use Illuminate\Support\Facades\Log;




class GeneratePaymentLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'payment-links:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate payment transaction links and send them via email to users';

    protected $paythruService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PaythruService $paythruService) 
    {
        parent::__construct();
        $this->paythruService = $paythruService; 
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
	// Fetch users whose payment date matches the current time
	   $desiredDate = now()->subHours(48);
       $users = PaymentDate::whereDate('payment_date', $desiredDate->toDateString())
           ->with('invitation')
           ->get();

        foreach ($users as $user) {
            $email = $user->invitation->email;
            $amount = $user->invitation->amount;

            // Generate payment link
            $paymentLink = $this->generatePaymentLink($user, $amount);

            // Send payment link via email
            $this->sendPaymentLinkByEmail($email, $paymentLink);

            $this->info('Payment link sent to ' . $email);
        }
    }

    private function generatePaymentLink($user, $amount)
    {
        $amount = $user->invitation->amount;
        $productId = env('PayThru_ajo_productid');
        $ajoId = $user->invitation->ajo_id; 
        $secret = env('PayThru_App_Secret');
        $hashSign = hash('sha512', $amount . $secret);
        $prodUrl = env('PayThru_Base_Live_Urlaqa');
        $token = $this->paythruService->handle();
	 if (!$token) {
        return response()->json([
            'error' => 'Access denied. You do not have permission to access this resource.'
        ], 403);
    }
        $data = [
            'amount' => $amount,
            'productId' => $productId,
            'transactionReference' => time() . $ajoId,
            'paymentDescription' => $user->invitation->token, 
            'paymentType' => 1,
            'sign' => $hashSign,
            'displaySummary' => true,
        ];

        $url = $prodUrl . '/transaction/create';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            $this->error('Failed to generate payment link for ' . $user->invitation->email);
            return null;
        } else {
            $transaction = json_decode($response->body(), true);
            $paymentLink =  $transaction['paylink'];
	    return $paymentLink;
        }
    }

    private function sendPaymentLinkByEmail($email, $paymentLink)
    {
        if ($paymentLink) {
            Mail::to($email)->send(new PaymentLinkMail($paymentLink));
        }
    }


    }


