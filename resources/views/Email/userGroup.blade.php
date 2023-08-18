!DOCTYPE html>
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
            


<p>Dear {{$uxer}}</p>

<p>Exciting news! Introducing Kontribute, another innovative AzatMe product. Like RefundMe, it's a social collection platform that transforms how we contribute and make a difference.</p>

<p>Kontribute enables you to rally support for various causes and projects. Whether it's a charity, personal goal, or community initiative, easily gather contributions from friends and family.</p>

<p>The best part? Kontribute sends a "Request to Pay" link to potential contributors, streamlining their support process. They can click the link and contribute effortlessly.</p>

<p>Take control of your fundraising journey. Craft compelling campaigns, share your story, and invite others to join your mission.</p>

<p>Rest assured, Kontribute prioritizes security, safeguarding your information and transactions.</p>

<p>Ready for this exciting venture? Register now to unlock Kontribute's potential and make a positive impact. Click the <b><a href= "https://azatme.com/register"> link</a></b> to start your journey.</p>

<p><b> <a href={{$slip['paylink']}}>Pay Now</a></b>.</p>

<p>Thank you for your support!</p>

<p>Best regards,</p>
<p>Team AzatMe</p>

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

</html>
