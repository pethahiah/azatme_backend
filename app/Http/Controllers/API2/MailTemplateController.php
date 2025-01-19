<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MailTemplate;
use App\Customer;
use App\Notifications\BusinessNotification;
use Illuminate\Support\Facades\Notification;

class MailTemplateController extends Controller
{
    //
    public function mailNotification(Request $request, $id) 
    {
    
    $owner = customer::find($id);

    //return $owner->id;

    //return $owner;

    $data = new MailTemplate([
        'email' =>$request->from,
        'to' => $owner->customer_name,
        'subject' => $request->subject,
        'salutation' => $request->salutation,
        'facebookLink' => $request->facebookLink,
        'whatsappNumber' => $request->whatsappNumber,
        'finalgreetings' => $request->finalgreetings,
        'campaignImage' =>$request->campaignImage,
        'url' => $request->url,
        'customer_id' => $owner->id,
    ]);
     if($request->campaignImage && $request->campaignImage->isValid())
                {
                $file_name = time().'.'.$request->campaignImage->extension();
                $request->campaignImage->move(public_path('images'),$file_name);
                $path = "https://azatme.eduland.ng/Azat_pay/public/images/promo/$file_name";
                $data->campaignImage = $path;
                }
                
                $data->save();


    Notification::send($owner, new BusinessNotification($data));
     return $data;
}

public function getAllMails()
    {
        $getUser = Auth::user()->id;
        $getAllCustomerEmail = MailTemplate::where('customer_id', $getUser)->get();
        return response()->json($getAllCustomerEmail);

    }

}
