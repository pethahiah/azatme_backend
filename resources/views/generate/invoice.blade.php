    <!DOCTYPE html>
<html>
<head>
    <title>Azatme.com</title>

<style type="text/css">
    body{
        font-family: Arial, sans-serif;
         background-color: #fff;
    }
    .m-0{
        margin: 0px;
    }
    .p-0{
        padding: 0px;
    }
    .pt-5{
        padding-top:5px;
    }
    .mt-10{
        margin-top:10px;
    }
    .text-center{
        text-align:center !important;
    }
    .w-100{
        width: 100%;
    }
    .w-50{
        width:auto;
    }
    .w-85{
        width:85%;
    }
    .w-15{
        width:15%;
    }
     .logo img{
        width:45px;
        height:45px;
        padding-top:30px;
    }
    .logo span{
        margin-left:8px;
        top:19px;
        position: absolute;
        font-weight: bold;
        font-size:25px;
    }
    .gray-color{
        color:#5D5D5D;
    }
 .text-bold{
        font-weight: bold;
    }
    .border{
        border:1px solid #4472c4;
    }
    table tr,th,td{
        border: 1px solid #4472c4;
        border-collapse:collapse;
        padding:7px 8px;
    }
    table tr th{
        background: #4472c4;
        font-size:15px;
            background-color: #4472c4;
        color: #fff;
    }
    table tr td{
        font-size:13px;
    }
    table{
        border-collapse:collapse;
    }
    .box-text p{
        line-height:10px;
    }
    .float-left{
        float:left;
    }
    .total-part{
        font-size:16px;
        line-height:12px;
    }
    .total-right p{
        padding-right:20px;
    }
   table tr:nth-child(even) {
        background-color: #fff;
    }
    .pay-now-button {
        display: inline-block;
        background-color: #4472c4;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }

    .header {
            width: 100%;
           padding: 20px 0;
            text-align: left;
        }
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 20px 0;
    text-align: center;
}

.business-logo {
    position: absolute; 
    top: 20px; 
    right: 20px; 
}


</style>

</head>
<body>
<div class="add-detail mt-10">
         <p class="text-center m-0 p-0">Invoice No - <span class="gray-color">{{$invoiceInfo->invoice_number}}</span></p>
        <p class="text-center m-0 p-0">Invoice Date - <span class="gray-color">{{$invoiceInfo->issue_date}}</span></p>
        <p class="text-center m-0 p-0">Due Date - <span class="gray-color">{{$invoiceInfo->due_date}}</span></p>
</div>
<div class="head-title">
    <div class="">
    <h1 class="m-0 pt-5 text-bold w-100">Invoice</h1>
    </div>
</div>

     <div class="">
    @if ($business->business_logo)
   <img src="{{ $business->business_logo }}" alt="Business logo" width="100" height="50" class="business-logo">
@else
    <p>No image to display.</p>
@endif
    </div>
    <div style="clear: both;"></div>


<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
        <tr>
            <td>
                <div class="box-text">
                    <p><strong>BILL FROM</strong>
                    <p>Name: {{ is_array($getBusiness) ? $getBusiness['name'] : $getBusiness->name }}</p>
                    <p>Address: {{ is_array($getBusiness) ? $getBusiness['address'] : $getBusiness->address }}</p>
                    <p>State: {{ is_array($getBusiness) ? $getBusiness['state'] : $getBusiness->state }},</p>
                    <p>Country: {{ is_array($getBusiness) ? $getBusiness['country'] : $getBusiness->country }}</p>
                </div>
            </td>
            <td>
                <div class="box-text">
                        <p><strong>BILL TO</strong></p>
                        <p>Customer Name: {{ $getUserInvo->customer_name }}</p>
                        <p>Customer Email:  {{ $getUserInvo->customer_email }}</p>
                        <p>Customer Code:  {{ $getUserInvo->customer_code }}</p>
                        <p>Customer Phone:  {{ $getUserInvo->customer_phone }}</p>
                </div>
            </td>
        </tr>
    </table>
</div>



<div class="table-section bill-tbl w-100 mt-10">
        <table class="table w-100 mt-10">
            <tr>
                <th class="w-50">ID</th>
	
                <th class="w-50">Description</th>
                <th class="w-50">Qty</th>
                <th class="w-50">Price</th>
                <th class="w-50">Total</th>
            </tr>
            @php
                $serialNumber = 1;
                $subtotal = 0;
                $vatt = 0;
            @endphp
            @foreach($InvoiceTran as $invoice)
                <tr align="center">
                    <td>{{$serialNumber++}}</td>
                    <td>{{$invoice->description}}</td>
                    <td>{{$invoice->qty}}</td>
                    <td>N{{number_format($invoice->transaction_amount, 2)}}</td>
                    <td>N{{number_format($invoice->transaction_amount * $invoice->qty, 2)}}</td>
                </tr>
                @php
                    $subtotal += $invoice->transaction_amount * $invoice->qty;
                    $vatt += $invoice->vat;
                @endphp
            @endforeach
            <tr>
                <td colspan="5">
                    <table class="w-100">
                        <tr>
                            <td class="total-left w-85 float-left" align="right">
                                Sub Total:<br>
                                Sales Tax 7.5%:<br>
                                Total:
                            </td>
                            <td class="total-right w-15 float-left" align="left">
                                N{{number_format($subtotal, 2)}}<br>
                                @if (is_numeric($invoice->vat) && $invoice->vat != 0)
                                    N{{number_format($vatt, 2)}}<br>
                                    N{{number_format($subtotal + $vatt, 2)}}
                                @else
                                    0<br>
                                    N{{number_format($subtotal, 2)}}
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <br>
<p>AMOUNT IN WORDS :{{ $word }} </p>

<p>TERMS AND CONDITIONS.</p>
<p>Please send payment within 30 days of receiving this invoice. There will be a 1.5% interest charge per month on late
invoices</p>
<p style="text-align: center;"> <a href="{{$paylink}}" class="pay-now-button">Pay Now</a>  </p>
<p style="color: #4472c4; text-align: center;">
    Thank you for your business!
</p>
<br>
           <div class="footer">
        <p>Invoice generated by AzatMe Business. Your social collection Platform. Visit <a"www.azatme.com">azatme.com</a> and get started.</p>
    </div>

</body>
</html> 





