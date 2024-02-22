<!DOCTYPE html>
<html>
<head>
    <title>AzatMe: Invitation to Join Ajo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        h1 {
            background-color: #4472c4;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        p {
            margin: 20px;
            font-size: 16px;
            color: #333;
        }

        a.accept {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            text-align: center;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
        }

        a.accept:hover {
            background-color: #45a049;
        }

        a.decline {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            text-align: center;
            text-decoration: none;
            background-color: #FF5733;
            color: white;
            border-radius: 5px;
        }

        a.decline:hover {
            background-color: #ff3a21;
        }
    </style>
</head>
<body>
    <h1>Invitation to Join Ajo</h1>
    
    <p>Hello {{ $userName }},</p>

    <p>You have been invited to join Ajo on AzatMe.</p>
    <p>Your payments period(s) is listed below:</p>
    <ul>
    @foreach($nextPaymentDates as $paymentDate)
        <li><strong>{{ $paymentDate }}</strong></li>
    @endforeach
</ul>

<p><strong>Collection Date: {{ $collectionDate }}</strong></p>


<br>
    <p>Click the following links to accept or decline the invitation:</p>

    <p>
        <a href="{{ $inviteLink }}&action=accept" class="accept">Accept Invitation</a>
   	<a href="{{ $inviteLink }}&action=decline" class="decline">Decline Invitation</a>
    </p>

    <p>If you do not wish to accept or decline this invitation, you can ignore this email.</p>

    <div id="thankYouMessage" style="display: none;">
        <p>You have declined the invitation.</p>
    </div>

    <script>
        function showThankYouMessage() {
            document.getElementById("thankYouMessage").style.display = "block";
        }
    </script>
</body>
</html>
