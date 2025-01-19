<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;
    public $paymentLink;
    public $ajoName;
    public $auth;
    public $day;
    public $ben;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($paymentLink, $ajoName, $auth, $day, $ben)
    {
        //
    $this->paymentLink = $paymentLink;
    $this->ajoName = $ajoName;
    $this->auth = $auth;
    $this->day = $day;
    $this->ben = $ben;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

	return $this->subject("Ajo Link/Paymentlink")->markdown('Email.payment_link')->with(['ajoName' => $this->ajoName, 'auth' => $this->auth, 'day' => $this->day, 'ben' => $this->ben]);;
    }
}

