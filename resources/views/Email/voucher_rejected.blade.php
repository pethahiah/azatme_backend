<!DOCTYPE html>
<html>
<head>
    <title>Voucher Rejected</title>
</head>
<body>
    <h2>Voucher Rejected</h2>
    <p>Dear {{ $sponsorName }},</p>
    <p>We regret to inform you that your voucher <strong>{{ $voucherCode }}</strong> worth <strong>₦{{ $voucherAmount }}</strong> has been rejected.</p>
    <p>Rejected on: {{ $updatedAt }}</p>

    @if(!empty($rejectionReason))
        <p><strong>Reason:</strong> {{ $rejectionReason }}</p>
    @endif

    <p>Please review and resubmit if necessary.</p>
</body>
</html>
