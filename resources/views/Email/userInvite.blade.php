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
              <p>Hi {{$uxer}},</p>
               <p>  I've got some incredible news coming your way! Your awesome friend <b>{{ ucfirst($authmail['name']) }}</b>, has discovered the fantastic world of RefundMe, one of the super cool AzatMe products. Get ready to embark on a thrilling adventure through a socialized payment collection platform like no other!</p>

               <p> Imagine this: You and your buddies are living your best lives, going on epic adventures, and making memories. But, oh no, those pesky bills start creeping in. Fear not! RefundMe is here to save the day, making it a breeze to request payment from friends and family for those shared expenses. It's time to turn the table on those bills and reclaim your financial freedom!</p>

               <p> With RefundMe, you hold the power to customize your payments like a true financial maestro. Splitting the bill equally or based on a percentage? It's entirely up to you! This exciting platform gives you the freedom to decide how to divide the expenses, making sure everyone chips in their fair share. No more awkward conversations or unpaid debts—RefundMe has got your back!. And here's the cherry on top: your account is locked up tighter than a treasure chest! You don't have to worry about sharing your personal information with anyone. It's like having your very own digital fortress, ensuring the utmost protection and security for your financial transactions. Peace of mind has never felt so good!</p>

              <p>   But hold onto your hats, because RefundMe isn't just for your squad—it's also a secret weapon for businesses. Picture this: Companies can receive payments without revealing their secret bank account details. How, you ask? Well, they can create system-generated invoices within the app, keeping their financial information under wraps while still getting paid. It's like a top-secret spy operation, but for payments!</p>

               <p> Now, are you ready to unleash the full power of RefundMe? Of course, you are! Don't wait another second—click this link to register and unlock a world of incredible features: <b><a href= "https://azatme.com/register"> Register</a></b>. Trust me, the journey is just beginning, and you don't want to miss out on all the thrills and surprises waiting for you!</p>

               <p>  Oh, and guess what? If you're feeling the excitement and want to make a payment right away to <b>{{ ucfirst($authmail['name']) }}</b>, simply click here:
                <b> <a href={{$slip['paylink']}}>Pay Now</a></b>. It's like activating a portal to a world where payments are made with a click of a button! </p>

<p> So, {{$uxer}}, get ready to step into a universe of exciting possibilities with RefundMe. It's time to reclaim control over your finances, have fun with your friends and family, and embrace a world where payments are seamless and secure. Don't hesitate—let the adventure begin!</p>

 <p>It’s Us @ Team AzatMe!</p>



                          <p>Thank you!</p>
                        <p><a href="www.azatme.com">AzatMe</a>.</p>
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
