<!DOCTYPE html>
<html>
<head>
    <title>Voucher Approved</title>
</head>
<body>
    <h2>Voucher Approved</h2>
    <p>Dear {{ $sponsorName }},</p>
    <p>Your voucher <strong>{{ $voucherCode }}</strong> worth <strong>₦{{ $voucherAmount }}</strong> has been approved.</p>
    <p>Approved on: {{ $updatedAt }}</p>
    <p>Thank you!</p>
</body>
</html>


