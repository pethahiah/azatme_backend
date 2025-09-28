<!DOCTYPE html>
<html>
<head>
    <title>User Blocked Notification</title>
</head>
<body>
    <p>Hello Admin,</p>
    <p>
        The user <strong>{{ $name }}</strong> ({{ $email }}) has been blocked after exceeding their fund limit.
    </p>
    <p>
        Attempted Amount: <strong>${{ number_format($amount, 2) }}</strong><br>
        Fund Limit: <strong>${{ number_format($fundLimit, 2) }}</strong><br>
        Total Attempts: <strong>{{ $attempts }}</strong>
    </p>
    <p>Please take any further actions if necessary.</p>
    <p>Regards,<br>Your Platform</p>
</body>
</html>
