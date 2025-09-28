namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FundLimitExceeded extends Mailable
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
        return $this->subject('Fund Limit Exceeded')
                    ->view('emails.fund_limit_exceeded')
                    ->with([
                        'name' => $this->user->name,
                        'amount' => $this->amount,
                        'fundLimit' => $this->fundLimit,
                    ]);
    }
}

