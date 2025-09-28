<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EncryptionHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\DirectDebitMandate; 
use Illuminate\Support\Facades\Mail; 
use App\Mail\DirectDebitNotificationMail; 

class ProcessAutomaticDeduction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $referenceId;
    protected $amount;
    protected $salt;
    protected $mandateNumber;
    protected $iv;
    protected $encryptionHelper;

    /**
     * Create a new job instance.
     *
     * @param  string  $referenceId
     * @param  float  $amount
     * @param  string  $salt
     * @param  string  $iv
     * @param  string  $mandateNumber
     * @param  EncryptionHelper  $encryptionHelper
     */
    public function __construct($referenceId, $amount, $salt, $iv, $mandateNumber, EncryptionHelper $encryptionHelper)
    {
        $this->referenceId = $referenceId;
        $this->amount = $amount;
        $this->salt = $salt;
        $this->iv = $iv;
        $this->mandateNumber = $mandateNumber;
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Prepare request data
            $requestData = [
                'mandateId' => $this->mandateNumber, 
                'narration' => 'Automatic deduction',
                'nameEnquiryReference' => $this->referenceId,
                'amount' => $this->amount
            ];

            // Encrypt the request data
            $encryptedData = $this->encryptionHelper->encrypt(json_encode($requestData), $this->salt, $this->iv);

            // Send request to the API
            $response = Http::post('https://www.sandbox.paythru.ng/debit/api/v1/directdebit/Pipeline/job/create', [
                'data' => $encryptedData
            ]);

            // Get the response data
            $responseData = $response->json();

            // Check if the response is successful
            if ($responseData['succeed'] && isset($responseData['data']['jobReference'])) {
              
                // Update DirectDebitMandate record with jobReference
                DirectDebitMandate::where('mandateId', $this->mandateNumber)->update([
                    'jobReference' => $responseData['data']['jobReference'],
                ]);

                Log::info("Direct debit successfully processed and updated for mandate ID {$this->mandateNumber}");
                
                 // Send an email notification
                Mail::to('support@pethahiah.com')->send(new DirectDebitNotificationMail($this->mandateNumber, $responseData['data']['jobReference']));
                
                
            } else {
                Log::error("Direct debit failed for mandate ID {$this->mandateNumber}: {$responseData['message']}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing direct debit for mandate ID {$this->mandateNumber}: " . $e->getMessage());
        }
    }
}
