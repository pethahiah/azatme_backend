<!DOCTYPE html>
<html>
<head>
    <title>RefundMe Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        .email-content {
            max-width: 800px;
            margin: 30px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .email-content p {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            text-align: justify;
        }

        .email-content b {
            font-weight: bold;
            color: #007bff;
        }

        .email-content a {
            text-decoration: none;
            color: #007bff;
        }

        .email-content a:hover {
            text-decoration: underline;
        }

        .logo {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 0 auto 20px;
        }

        .email-content .register-link,
        .email-content .pay-now-link {
            display: block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
        }

        .footer {
            text-align: center;
        }

        .footer p {
            margin-bottom: 5px;
        }

        .social-icons {
            text-align: center;
        }

        .social-icon {
            display: inline-block;
            margin: 0 5px;
        }

        .social-icon img {
            width: 32px; /* Adjust the size as needed */
            height: 32px; /* Ensure height matches width for square icons */
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 600px) {
            .email-content {
                padding: 15px;
            }

            .email-content p {
                font-size: 14px;
            }
        }

    </style>
</head>
<body>
<div class="email-content">
    <img src="https://api.azatme.com/storage/profiles/3kUvALFhEySvs7uOgDA8OfDmsrbvCPrj9e6aUMFD.png" alt="AzatMe Image" class="logo">


    <p>Dear {{ucfirst($uxer)}},</p>
    <p>Exciting news! Introducing one of AzatMe’s product, called Kontribute. With it,</p>
    <ul>
        <li>You can effortlessly create fundraising campaigns for your Causes, Charity, Personal Goals, or Community Initiatives.</li>
        <li>Share the generated Kontribute Pay link far and wide.</li>
        <li>Gather contributions from associates/friends/family and make a positive impact!</li>
    </ul>

    <p> Wait a minute! You have been invited to make a Kontribute.Click <b><a href="{{$slip['paylink']}}">Pay Now</a></b> to contribute, to the cause.</p>
    <p>Can't access the button? kindly use the link: <b><a href="{{$slip['paylink']}}">{{$slip['paylink']}}</a>.</b></p>
    <p>Furthermore, experience the amazing world of AzatMe Products. Join using this link:  <b><a href="https://azatme.com/register">https://azatme.com/register</a></b></p>
    <p>Thank you!</p>
    <p>It’s Us @ Team AzatMe!</p>

    <!-- Footer with social media links -->
    <div class="footer">
        <p style="text-align: center !important;">Follow us on social media</p>
        <div class="social-icons">
            <a href="#" class="social-icon" target="_blank"><img src="https://api.azatme.com/storage/profiles/1QfW8snv5yETjhPlWPDYODXPjWfTrIN4kdCVwn2W.jpg" alt="Facebook"></a>
            <a href="#" class="social-icon" target="_blank"><img src="https://api.azatme.com/storage/profiles/5MI8cWhvXgQRuwmS2gy3Dy0R75qMlojG3403gstr.jpg" alt="Twitter"></a>
            <a href="#" class="social-icon" target="_blank"><img src="https://api.azatme.com/storage/profiles/fNPOlac1mDuLHreE9mF4UPB2ZFdPaqCnphl4zWvN.jpg" alt="Instagram"></a>
        </div>
        <p style="text-align: center !important;">
            © <span id="currentYear"></span> <a href="www.paythru.ng">PayThru</a>. All rights reserved.
        </p>
    </div>
</div>

<script>
    // Get the current year
    const currentYear = new Date().getFullYear();
    // Set the current year to the "currentYear" span element
    document.getElementById("currentYear").innerText = currentYear;
</script>
</body>
</html>

