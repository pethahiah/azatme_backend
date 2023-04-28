<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendUserInviteMail extends Mailable
{
    use Queueable, SerializesModels;
    public $slip;
    public $authmail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($slip, $authmail)
    {
        $this->slip = $slip;
        $this->authmail = $authmail;
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("User Invite and Paymentlink")->markdown('Email.userInvite')->with(['authmail' => $this->authmail]);
        
    }
}
