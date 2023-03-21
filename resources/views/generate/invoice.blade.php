<!DOCTYPE html>
<html>
<head>
    <title>Azatme.com</title>
</head>
<style type="text/css">
    body{
        font-family: 'Roboto Condensed', sans-serif;
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
        width:50%;   
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
        border:1px solid black;
    }
    table tr,th,td{
        border: 1px solid #d2d2d2;
        border-collapse:collapse;
        padding:7px 8px;
    }
    table tr th{
        background: #F4F4F4;
        font-size:15px;
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
</style>
 
<body>
<div class="head-title">
    <h1 class="text-center m-0 p-0">Invoice</h1>
</div>
<div class="add-detail mt-10">
    <div class="w-50 float-left mt-10">
        <p class="m-0 pt-5 text-bold w-100">Invoice No - <span class="gray-color">{{$invoice->invoice_number}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Invoice Code - <span class="gray-color">{{$invoice->unique_code}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Order Date - <span class="gray-color">{{$invoice->issue_date}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Due Date - <span class="gray-color">{{$invoice->due_date}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Due Days - <span class="gray-color">{{$invoice->due_days}}</span></p>
    </div>
     <div class="w-20 float-left logo mt-20">
     
    </div>
    <div style="clear: both;"></div>
</div>
        </tr>

</table>
</div>
<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
        <tr>
            <th class="w-50">From</th>
            <th class="w-50">To</th>
        </tr>
        <tr>
            <td>
                <div class="box-text">
                    <p><strong>Name: </strong>{{$getBusiness->name}}</p>
                    <p><strong>Address: </strong>{{$getBusiness->address}}</p>
                    <p><strong>State: </strong>{{$getBusiness->state}},</p>
                    <p><strong>Country: </strong>{{$getBusiness->country}}</p>
                    
                </div>
            </td>
            <td>
                <div class="box-text">
                    <p><strong>Customer Name:</strong> {{$getUserInvo->customer_name}}</p>
                    <p><strong>Customer Email:</strong>  {{$getUserInvo->customer_email}}</p>
                    <p><strong>Customer Code:</strong>  {{$getUserInvo->customer_code}}</p>
                    <p><strong>Customer Phone:</strong>  {{$getUserInvo->customer_phone}}</p>
                    
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
        <tr>
            <th class="w-50">Product Name</th>
            <th class="w-50">Product Description</th>
            <th class="w-50">Price</th>
            <th class="w-50">Qty</th>
            <th class="w-50">VAT</th>
            <th class="w-50">Grand Total</th>
        </tr>
        <tr align="center">
            <td>{{$invoice->name}}</td>
            <td>{{$invoice->description}}</td>
            <td>{{$invoice->transaction_amount}}</td>
            <td>{{$invoice->qty}}</td>
            <td>{{$invoice->vat}}</td>
            <td>{{$invoice->Grand_total}}</td>
        </tr>
        <tr>
            <!--<td colspan="7">-->
            <!--    <div class="total-part">-->
            <!--        <div class="total-left w-85 float-left" align="right">-->
            <!--            <p>Account Number:</p>-->
            <!--            <p>Bank Name:</p>-->
                        
            <!--        </div>-->
            <!--        <div class="total-right w-15 float-left text-bold" align="left">-->
            <!--            <p>{{$invoice->account_number}}</p>-->
            <!--            <p>{{$invoice->bankName}}</p>-->
                       
                       
            <!--        </div>-->
            <!--        <div style="clear: both;"></div>-->
            <!--    </div> -->
            <!--</td>-->
        </tr>
    </table>
</div>

<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
         <tr>
            <th>Kindly click the link to make payment</th>
            <th class="w-50">{{$paylink}}</th>
            
        </tr>
      
       
    </table>
</div>
</html>