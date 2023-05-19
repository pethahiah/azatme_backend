<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessNotification extends Notification
{
    use Queueable;

    private $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
       
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->from($this->notification['email'])
                    ->line($this->notification['subject'])
                    ->line($this->notification['salutation'])
                    ->line($this->notification['facebookLink'])
                    ->line($this->notification['whatsappNumber'])
                    ->line($this->notification['url'])
                    ->line($this->notification['finalgreetings'])
                    ->line($this->notification['campaignImage'])
                    ->view('Email.template', ['notification' => $this->notification]);
                    
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
