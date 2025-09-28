<!DOCTYPE html>
<html>
<head>
    <title>Fund Limit Exceeded</title>
</head>
<body>
    <p>Dear {{ $name }},</p>
    <p>
        Your recent fund request of <strong>${{ number_format($amount, 2) }}</strong> 
        exceeds your current fund limit of <strong>${{ number_format($fundLimit, 2) }}</strong>.
    </p>
    <p>Please review your fund limit or contact support for assistance.</p>
    <p>Thank you,<br>Support Team</p>
</body>
</html>
