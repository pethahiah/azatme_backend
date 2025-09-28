<?php

namespace App\Mail;


use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;




class UserFlaggedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $action;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $action, $reason)
    {
        $this->user = $user;
        $this->action = $action;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->action === 'flag' 
            ? 'Your Account Has Been Flagged' 
            : ($this->action === 'block' ? 'Your Account Has Been Blocked' : '');

        return $this->subject($subject)
                    ->view('emails.user_flagged_notification')
                    ->with([
                        'userName' => $this->user->name,
                        'action'   => ucfirst($this->action),
                        'reason'   => $this->reason,
                    ]);
    }

}
