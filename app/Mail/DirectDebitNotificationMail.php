<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DirectDebitNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mandateNumber;
    public $jobReference;

    /**
     * Create a new message instance.
     *
     * @param  string  $mandateNumber
     * @param  string  $jobReference
     * @return void
     */
    public function __construct($mandateNumber, $jobReference)
    {
        $this->mandateNumber = $mandateNumbe;
        $this->jobReference = $jobReference;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Direct Debit Processed')
                    ->view('Email.directdebitnotification')
                    ->with([
                        'mandateId' => $this->mandateNumber,
                        'jobReference' => $this->jobReference,
                    ]);
    }
}
