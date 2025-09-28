<!DOCTYPE html>
<html>
<head>
    <title>Business Email</title>
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

        /*.email-content .register-link,*/
        /*.email-content .pay-now-link {*/
        /*    display: block;*/
        /*    background-color: #007bff;*/
        /*    color: #fff;*/
        /*    padding: 10px;*/
        /*    border-radius: 5px;*/
        /*    margin-top: 15px;*/
        /*    text-align: center;*/
        /*    max-width: 150px; !* Adjust the max-width as needed *!*/
        /*    margin-left: auto;*/
        /*    margin-right: auto;*/
        /*}*/

        .footer {
            text-align: center;
        }

        .footer p {
            margin-bottom: 5px;
        }

        .social-icons-container {
            text-align: center;
        }

        .follow-text {
            margin-bottom: 10px;
        }

        .social-icons {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .social-icon {
            margin: 0 10px;
        }

        .social-icon img {
            width: 40px;
            height: 40px;
        }

        .centerx {
            text-align: center;
        }

        @media screen and (max-width: 600px) {
            .email-content {
                padding: 15px;
            }

            .email-content .recipient {
                font-size: 18px;
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
    <p>Dear {{ucfirst($cusName)}},</p>
    <p>Further to the business relationship with <b>{{ ucfirst($busName) }}</b>, You are receiving the AzatMe Business payment link from <b>{{ ucfirst($busName) }}</b>. AzatMe Business allows Merchant to generate invoice, facilitate collections, serve as POS, keep track of transactions, and export into 3rd party accounting solutions.</p>
    <p>Please proceed to make the payment using the provided link below:<b><a href="{{$paylink}}" class="pay-now-link">Pay Now</a></b></p>

    <p>Kindly <b><a href="https://azatme.com/register" class="register-link">Register</a></b> and unlock amazing features of AzatMe Business. Be you a Nano or Micro or gig business, you will find innovative solutions for your needs. You don't want to miss out!</p>
    <p>If you can't access the button, you can copy and paste the following link into your browser to make the payment: <b><a href="{{$paylink}}">{{$paylink}}</a></b>.</p>
    <p>To register, use this link: <b><a href="https://azatme.com/register">https://azatme.com/register</a></b></p>
    <p>Thank you!</p>
    <p>It’s Us @ Team AzatMe!</p>
    <p><a href="https://www.azatme.com">AzatMe</a></p>

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
