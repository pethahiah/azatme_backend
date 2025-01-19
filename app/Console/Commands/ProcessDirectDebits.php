<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EncryptionKeyService;
use App\Services\EncryptionHelper;
use App\Jobs\ProcessAutomaticDeduction;
use Illuminate\Support\Facades\Http;
use App\PaymentDate;
use App\Invitation;
use App\Bank;
use Carbon\Carbon;
use App\DirectDebitMandate;

class ProcessDirectDebits extends Command
{
    protected $signature = 'directdebit:process';
    protected $description = 'Process direct debit operations daily';

    protected $encryptionKeyService;
    protected $encryptionHelper;

    public function __construct(EncryptionKeyService $encryptionKeyService, EncryptionHelper $encryptionHelper)
    {
        parent::__construct();
        $this->encryptionKeyService = $encryptionKeyService;
        $this->encryptionHelper = $encryptionHelper;
    }
    
    
   public function handle()
{
    try {
        $encryptionKeys = $this->encryptionKeyService->generateKey();
        $salt = $encryptionKeys['salt'];
        $iv = $encryptionKeys['iv'];

        $dueAjoUsers = $this->getDueAjoUsers();
        
        foreach ($dueAjoUsers as $dueUser) {
            $invitation = Invitation::where('id', $dueUser['invitation_id'])->first();
            $user = User::where('email', $invitation->email)->first();
            $getmandateId = DirectDebitMandate::where('ajo_id', $invitation->ajo_id)->where('email', $invitation->email)->first();
            $mandateNumber = $getmandateId-mandateId;

            if ($user) {
                $bankDetails = Bank::where('user_id', $user->id)->first();

                if ($bankDetails) {
                    $accountNumber = $bankDetails->account_number;
                    $bankCode = $bankDetails->bank_code;

                    $response = Http::post('https://www.sandbox.paythru.ng/debit/api/v1/directdebit/Pipeline/name-check', [
                        'bankCode' => $bankCode,
                        'accountNumber' => $accountNumber
                    ]);

                    $responseData = $response->json();

                    if ($responseData['succeed']) {
                        ProcessAutomaticDeduction::dispatch(
                            $responseData['data']['referenceId'],
                            $dueUser['amount'], 
                            $salt,
                            $iv,
                            $this->encryptionHelper,
                            $mandateNumber
                        );
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $this->error('Error: ' . $e->getMessage());
    }
}




    protected function getDueAjoUsers()
    {
        $owners = PaymentDate::whereDate('collection_date', Carbon::today()->toDateString())->get();

        $dueAjoUsers = [];
        foreach ($owners as $owner) {
            $ajoBenefit = Invitation::where('id', $owner->invitation_id)->first();
            $ajoIds = $ajoBenefit->ajo_id;
            $amount = $ajoBenefit->amount;

            $users = PaymentDate::whereDate('payment_date', Carbon::today()->toDateString())
                ->with('invitation')
                ->whereHas('invitation', function ($query) use ($ajoIds) {
                    $query->where('ajo_id', $ajoIds);
                })
                ->get();

            foreach ($users as $user) {
                $dueAjoUsers[] = [
                    'invitation_id' => $owner->invitation_id,
                    'amount' => $amount,
                ];
            }
        }

        return $dueAjoUsers;
    }
}
