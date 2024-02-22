<!DOCTYPE html>
<html>
<head>
    <title>Payment Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        p {
            margin: 0 0 15px;
        }
        a.button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
<p>Dear User,</p>
<p>The Ajo transaction with the name <b>{{$ajoName}}</b>, initiated by <b>{{ucfirst($auth)}}</b> for payment to <b>{{ucfirst($ben)}}</b>, is due <b>{{$day}}</b>. Please proceed to make the payment using the provided details:</p>
<p>Here is your payment link:</p>

<a href="{{ $paymentLink }}" class="button">Pay Now</a>

<p>Thank you for choosing AzatMe!</p>
    </div>
</body>
</html>

