<!DOCTYPE html>
<html>
<head>
    <title>User Status Notification</title>
</head>
<body>
    <p>Dear {{ $userName }},</p>

    <p>Your account has been {{ $action }}.</p>

    <p><strong>Reason:</strong> {{ $reason }}</p>

    <p>If you believe this action was a mistake, please contact support.</p>

    <p>Thank you,<br>The Admin Team</p>
</body>
</html>
