<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KontributMail extends Mailable
{
    use Queueable, SerializesModels;

    public $slip;
    public $authmail;
    public $uxer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($slip, $authmail, $uxer)
    {
        //

        $this->slip = $slip;
	$this->authmail = $authmail;
        $this->uxer = $uxer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
       // return $this->subject("Kontribute Invite/Paymentlink")->markdown('Email.userGroup')->with('authmail' => $this->authmail);
	return $this->subject("Kontribute Invite/Paymentlink")->markdown('Email.userGroup')->with(['authmail', $this->authmail, 'uxer'=> $this->uxer]);
	//return $this->subject("User Invite and Paymentlink")->markdown('Email.userInvite')->with(['authmail' => $this->authmail, 'uxer' => $this->uxer]);
    }
}
