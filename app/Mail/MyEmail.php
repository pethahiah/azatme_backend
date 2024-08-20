<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MyEmail extends Mailable
{
    use Queueable, SerializesModels;

//    public $inviteLink;
  //  public $name;
    //public $nextPaymentDates;
    public $inviteLink;
    public $userName;
    public $nextPaymentDates;
    public $collectionDate;

    /**
     * Create a new message instance.
     *
     * @return void
     */


    public function __construct($inviteLink, $userName, $nextPaymentDates, $collectionDate)
    
    {
        //
//	$this->inviteLink = $inviteLink;
  //      $this->name = $name;
    //    $this->nextPaymentDates = $nextPaymentDates;
	$this->inviteLink = $inviteLink;
        $this->userName = $userName;
        $this->nextPaymentDates = $nextPaymentDates;
        $this->collectionDate = $collectionDate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */

    public function build()
{
    return $this->subject('Ajo Invitation')
                ->markdown('Email.invitation')
                ->with([
                    'inviteLink' => $this->inviteLink,
                    'userName' => $this->userName,
                    'nextPaymentDates' => $this->nextPaymentDates,
                    'collectionDate' => $this->collectionDate,
                ]);
}

}
