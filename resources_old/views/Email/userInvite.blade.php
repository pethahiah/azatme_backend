<!DOCTYPE html>
<html>
<head>
    <title>RefundMe Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles.css">

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
              <p>Dear {{$uxer}},</p> 
		<p> Your awesome friend <b>{{ ucfirst($authmail['name']) }}</b>, has discovered the fantastic world of RefundMe, one of the super cool AzatMe Products, a socialized payment collection platform like no other!</p>

               <p> RefundMe allows you to request payment from friends and family for those shared expenses. This exciting platform gives you the freedom to decide how to divide the expenses, making sure everyone chips in their fair share. No more awkward conversations or unpaid debts-RefundMe has got your back! You don't have to worry about sharing your personal information with anyone. </p>
               <p>Kindly <b><a href= "https://azatme.com/register"> Register</a></b> and unlock amazing world of incredible features. You don't want to miss out! Oh, to pay <b>{{ ucfirst($authmail['name']) }}</b>, right away, click  <b> <a href={{$slip['paylink']}}>Pay Now</a></b>. Its payments simplified at click of a button! It's time to reclaim control over your finances, have fun with your friends and family, and embrace a world where payments are seamless and secure. </p>
<p>If you can't access the button, you can manually make the payment by following this link: <b><a href="{{$slip['paylink']}}">{{$slip['paylink']}}</a>.</b></p>

<p>And also register using this link: <b><a href= "https://azatme.com/register">https://azatme.com/register</a></b></p>
      		<p>Thank you!</p>
 		<p>It’s Us @ Team AzatMe!</p>
                        <p><a href="www.azatme.com">AzatMe</a>.</p>
<div class="footer">
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

<p class="centerx">
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

</html>
