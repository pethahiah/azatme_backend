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
              <p>Dear {{$cusName}},</p> 
		<p> 
		You've received a payment link in response to your business transaction request with <b>{{ ucfirst($busName) }}</b>, Please proceed to make the payment using the provided link below:
		</p>
		 <p><b> <a href={{$paylink}}>Pay Now</a></b></p>
               <p> AatMe Business allows you to request payment from your business partners/customers for those transactions.</p>
               <p>Kindly <b><a href= "https://azatme.com/register"> Register</a></b> and unlock amazing world of incredible features. You don't want to miss out! 
 </p>
<p>
If you can't access the button, you can manually make the payment by following this link: <b><a href="{{$paylink}}">{{$paylink}}</a>.</b></p>
<p>And also register using this link: <b><a href= "https://azatme.com/register">https://azatme.com/register</a></b></p>
      		<p>Thank you!</p>
 		<p>It’s Us @ Team AzatMe!</p>
                        <p><a href="www.azatme.com">AzatMe</a>.</p>
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

</html>
