<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BusinessPaylinkMail extends Mailable
{
    use Queueable, SerializesModels;
    public $paylink;
    public $busName;
    public $cusName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($paylink, $busName, $cusName)
    {
        //
        $this->paylink = $paylink;
	$this->cusName = $cusName;
	$this->busName = $busName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //return $this->subject("Business Invite/Paymentlink")->view('Email.businessInvite');
	 return $this->subject("Business Paymentlink")->view('Email.businessInvite')->with(['busName' => $this->busName, 'cusName' => $this->cusName]);
    }
}
