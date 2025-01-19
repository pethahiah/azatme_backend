<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErrorMail extends Mailable
{
    use Queueable, SerializesModels;

	public $errorMessage;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($errorMessage)
    {
        //
	$this->errorMessage = $errorMessage;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('payThru-Auth-Error')
            ->subject('Error Occurred')
            ->view('emails.error')
            ->with([
                'error' => $this->errorMessage,
            ]);
    }
}
