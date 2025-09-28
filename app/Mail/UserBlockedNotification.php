<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserBlockedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $amount;
    public $fundLimit;

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $amount
     * @param $fundLimit
     */
    public function __construct($user, $amount, $fundLimit)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->fundLimit = $fundLimit;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('User Blocked Notification')
                    ->view('emails.user_blocked_notification')
                    ->with([
                        'name'      => $this->user->name,
                        'email'     => $this->user->email,
                        'amount'    => $this->amount,
                        'fundLimit' => $this->fundLimit,
                        'attempts'  => $this->user->sec_flag,
                    ]);
    }
}

