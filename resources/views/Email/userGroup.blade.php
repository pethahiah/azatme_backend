<!DOCTYPE html>
<html>
<head>
    <title>RefundMe Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles.css">
<<<<<<< HEAD

<style>
/* styles.css */

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
    color: #007bff; /* Blue link color */
}

.email-content a {
    text-decoration: none;
    color: #007bff; /* Blue link color */
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


/* Additional styling for the link buttons */
.email-content .register-link,
.email-content .pay-now-link {
    display: block;
    background-color: #007bff;
    color: #fff;
    padding: 10px 15px;
    border-radius: 5px;
    margin-top: 15px;
}


.footer {
  text-align: center;
}

.footer p {
  margin-bottom: 5px;
}

.social-icons {
  text-align: center; /* Center the social media icons */
}

.social-icons a {
  display: inline-block; /* Display the social media icons in a row */
  margin: 5px; /* Add some spacing between the icons */
}

.social-icons img {
  max-width: 30px;
  height: auto;
}

.center {
  text-align: center; /* Center the copyright text */
}



/* Mobile Responsive Styles */
@media screen and (max-width: 600px) {
    .email-content {
        padding: 15px; /* Reduce padding for smaller screens */
    }
    .email-content .recipient {
        font-size: 18px; /* Increase font size for recipient name on mobile */
    }
    .email-content p {
        font-size: 14px; /* Decrease font size for paragraphs on mobile */
   }
}


</style>
</head>
<body>
    <div class="email-content">

 <img src="https://api.azatme.com/storage/profiles/3kUvALFhEySvs7uOgDA8OfDmsrbvCPrj9e6aUMFD.png" alt="AzatMe Image">
            

<p>Dear {{ucfirst($uxer)}}</p>,
Exciting news! Kontribute, one of the many products from AzatMe, allows users to easily create fundraising campaigns for Causes, Charity, Personal Goals, or Community Initiatives. Users can share a "Request to Pay" link to gather contributions from friends/family. The platform aims to streamline the contribution process and help users rally support. Key features include crafting campaigns, sharing your story, inviting others to join, and prioritizing security. 
Kontribute sends a Request to Pay link to potential contributors, streamlining their support process. They can click the link and contribute effortlessly. Start using Kontribute to make a positive impact. Click the link to start your journey.
<p>Click <b> <a href={{$slip['paylink']}}>Pay Now</a></b>.</p>
<p>If you can't access the button, you can manually make the payment by following this link: <b><a href="{{$slip['paylink']}}">{{$slip['paylink']}}</a>.</b></p>

<p>And also register using this link: <b><a href= "https://azatme.com/register">https://azatme.com/register</a></b></p>
<p>Thank you!</p>
<p>It’s Us @ Team AzatMe!</p>

<!-- Footer with social media links -->
<div class="footer">
  <p>Follow us on social media:</p>
  <div class="social-icons">
    <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/1QfW8snv5yETjhPlWPDYODXPjWfTrIN4kdCVwn2W.jpg" alt="Facebook"></a>
    <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/NkthvQH4hIi0p1touhtRZP5wB2VNiI70S7eQ2UBb.png" alt="Twitter"></a>
    <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/fNPOlac1mDuLHreE9mF4UPB2ZFdPaqCnphl4zWvN.jpg" alt="Instagram"></a>
  </div>

<p class="center">
  © 2023<span id="currentYear"></span> <a href="www.paythru.ng">PayThru</a>. All rights reserved.
</p>
</div>
</div>
</body>


<script>
// Get the current year
  const currentYear = new Date().getFullYear();

  // Set the current year to the "currentYear" span element
  document.getElementById("currentYear").innerText = currentYear;
</script>

=======

    <style>
        /* styles.css */

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


        /* Additional styling for the link buttons */
        .email-content .register-link,
        .email-content .pay-now-link {
            display: block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
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

        .social-icons a {
            display: inline-block;
            margin: 5px;
        }

        .social-icons img {
            max-width: 30px;
            height: auto;
        }

        .center {
            text-align: center;
        }

        .social-icons.center {
            display: flex;
            justify-content: center; /* Center aligns social icons */
        }




        /* Mobile Responsive Styles */
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

    <img src="https://api.azatme.com/storage/profiles/3kUvALFhEySvs7uOgDA8OfDmsrbvCPrj9e6aUMFD.png" alt="AzatMe Image">


    <p>Hi {{ucfirst($uxer)}}</p>,
    Exciting news! Introducing one of AzatMe’s product, called Kontribute. With it,
    <ul>
        <li>You can effortlessly create fundraising campaigns for your Causes, Charity, Personal Goals, or Community Initiatives.</li>
        <li>Share the generated Kontribute Pay link far and wide.</li>
        <li>Gather contributions from associates/friends/family and make a positive impact!</li>
    </ul>
    <p>Wait a minute!! You have been invited to make a Kontribute. Click <b><a href={{$slip['paylink']}}>Pay Now</a></b> to contribute to the cause.</p>
    <p>Can't access the button? kindly use the link below: <b><a href="{{$slip['paylink']}}">{{$slip['paylink']}}</a>.</b></p>
    <p>Furthermore, experience the amazing world of AzatMe Products. Join using this link: <b><a href="https://azatme.com/register">https://azatme.com/register</a></b></p>
    <p>Cheers!</p>
    <p>Team AzatMe</p>

    <!-- Footer with social media links -->
    <div class="footer">
        <p style="text-align: center !important;">Follow us on social media</p>
        <div class="social-icons">
            <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/1QfW8snv5yETjhPlWPDYODXPjWfTrIN4kdCVwn2W.jpg" alt="Facebook" style="width: 30px !important; height: 30px !important;"></a>
            <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/5MI8cWhvXgQRuwmS2gy3Dy0R75qMlojG3403gstr.jpg" alt="Twitter"></a>
            <a href="#" target="_blank"><img src="https://api.azatme.com/storage/profiles/fNPOlac1mDuLHreE9mF4UPB2ZFdPaqCnphl4zWvN.jpg" alt="Instagram"></a>
        </div>

        <p style="text-align: center !important;">
            © 2023<span id="currentYear"></span> <a href="www.paythru.ng">PayThru</a>. All rights reserved.
        </p>

    </div>
</div>
</body>


<script>
    // Get the current year
    const currentYear = new Date().getFullYear();

    // Set the current year to the "currentYear" span element
    document.getElementById("currentYear").innerText = currentYear;
</script>

>>>>>>> db461c39f8113664d23d80a88431c69b77ccaa72
</html>
