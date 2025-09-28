<?php

namespace App\Mail;

use App\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class VoucherApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $voucher;
    public $status;

    public function __construct(Voucher $voucher, string $status)
    {
        $this->voucher = $voucher;
        $this->status = $status;
    }

   public function build()
{
    $subject = $this->status === 'approved' ? 'Voucher Approved' : 'Voucher Rejected';
    $view = $this->status === 'approved' ? 'Email.voucher_approved' : 'Email.voucher_rejected';

    return $this->subject($subject)
        ->view($view)
        ->with([
            'voucherCode' => $this->voucher->voucher_code,
            'voucherAmount' => $this->voucher->voucher_amount,
            'sponsorName' => optional($this->voucher->sponsor)->name ?? 'Unknown Sponsor',
            'updatedAt' => $this->voucher->updated_at ?? now(),
            'rejectionReason' => $this->voucher->rejection_reason ?? '',
        ]);
}

}
