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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($slip)
    {
        //

        $this->slip = $slip;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Kontribute Invite/Paymentlink")->markdown('Email.userGroup');
    }
}
