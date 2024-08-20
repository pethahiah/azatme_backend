<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InquiryMail extends Mailable
{

    use Queueable, SerializesModels;

    public $data;
    /**
     * Create a new message instance.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Inquiry')
            ->view('Email.inquiry')
            ->with(['data' => $this->data]);
    }
}
