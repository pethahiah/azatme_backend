<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Auth;
use App\Verifysms;
use Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;




class MobileVerificationController extends Controller
{
    // TWILIO INTEGRATION
    public function verifyPhone(Request $request)
{
  $otp = rand(1000,9999);
  Log::info("otp = ".$otp);
  $phone = request()->get('phone');
  $uxer = user::where('phone', '=', $phone)->first()->phone;

  try{
    $account_sid = env('TWILIO_SID');
    $auth_token = env('TWILIO_TOKEN');
    $twilio_number = env('TWILIO_FROM');

    $toks = Verifysms::create([
      'phone' => $request->phone,
      'otp' => $otp,
      'user_id' => Auth::user()->id,
  ]);

    $client = new Client($account_sid, $auth_token);
    $client->messages->create($uxer, [
      'from' => $twilio_number,
      'body' => $otp]);

      return response([
        'message' => 'OTP sent successfully'
    ], 201);

  } catch (Exception $e) {
      dd("Error: ". $e->getMessage());
  }
 }

 
 public function checkOtp(Request $request)
 {
  $mobileverification  = Verifysms::where([['phone','=', $request->phone],['otp','=',$request->otp]])->first();
        if($mobileverification){
            Verifysms::where('phone','=',$request->phone)->update(['otp' => null]);
            return response(["status" => 200, "message" => "Success"]);
        }
        else{
            return response(["status" => 401, 'message' => 'Invalid Credentials']);
        }
 }

// CHECK EMAIL VALIDITY
 public function EmailVerification(Request $request)
    {

        $otp = random_int(0, 999999);
        $otp = str_pad($otp, 6, 0, STR_PAD_LEFT);
        Log::info("otp = ".$otp);
        
        $checkAuth = Auth::user()->id;
        $checkAuths = Auth::user()->email;
        $email = request()->get('email');
        $medium = request()->get('medium');
        $otp_expires_time = Carbon::now()->addMinutes(15);
        
        $uxer = User::where('email', '=', $email)->first()->email;
          if($uxer)
        {
            if($uxer != $checkAuths)
                {
                    return response([
                'message' => 'You are not authorize'
            ], 403);
                   
                }else{
            $user = User::where('phone', $email)->orWhere('email', $email)->update(['otp' => $otp]);
            $toks = Verifysms::create([
              'email' => $request->email,
              'otp' => $otp,
              'medium' => $medium,
              'otp_expires_time' => $otp_expires_time,
              'user_id' => Auth::user()->id,
          ]);    
         
            $data =  [
              'otp' => $otp,
              'email' => $request->email
            ];
            
            $subject = 'AzatMe: Email Verification';
            Mail::send('Email.verifictaion', $data, function($message) use($request,$subject){
                $message->to($request->email)->subject($subject);
            });
            return response(["status" => 200, "message" => "OTP has been sent to your mail"]);  
                }
    }else{   
        return response()->json([
            'message' => 'Record not found.'
        ], 404);
  }        
        }

// VERIFY EMAIL VALIDITY
    public function ConfirmEmailViaOtp(Request $request)
    {
    $user  = Verifysms::where([['email','=',$request->email],['otp','=',$request->otp]])->first();
//return now();
        if($user){
            if((now() > $user->otp_expires_time->toDateTimeString()))
                {
                    return response([
                'message' => 'OTP Already expired'
            ], 409);
                   
                }else{
            User::where('email','=',$request->email)->update(['otp' => null]);
            return response(["status" => 200, "message" => "Email Confirmed"]);
        }
        }
        else{
            return response([
                'message' => 'Invalid otp'], 401);
        }  
    }

// TERMII SMS GATEWAY INTEGRATION
    public function getPhoneNumber(Request $request)
    {
      $otp = random_int(0, 999999);
      $otp = str_pad($otp, 6, 0, STR_PAD_LEFT);
      Log::info("otp = ".$otp);
      $phone = request()->get('phone');
      $uxer = user::where('phone', '=', $phone)->first()->phone;
      try{ 

      $ApiKey = env('TermiiApiKey');
      $Sid = env('AzatNumber');

      $data = [
        "ApplicationId" => $ApiKey,
        "from" => $Sid,
        "message_type" => "NUMERIC",
        "to" => $uxer,
        "channel" => "generic",
        "type" => "plain",
        "pin_attempts" => 3,
        "pin_time_to_live" =>  5,
        "pin_length" => 6,
        "pin_placeholder" => $otp,
        "message_text" => $otp,
        "pin_type" => "NUMERIC"
      ];
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
  ])->post('https://termii.com/api/sms/send', $data);
    //return $response;
    // $response->messages->create($uxer, [
    //   'from' => $Sid,
    //   'sms' => $otp]);

          $toks = Verifysms::create([
          'phone' => $request->phone,
          'otp' => $otp,
          'medium' => $request->mediun,
          'user_id' => Auth::user()->id,
      ]);    
          return response([
            'message' => 'OTP sent successfully'
        ], 201);
    
      } catch (Exception $e) {
          dd("Error: ". $e->getMessage());
      }
      
     }


     public function confirmPhoneOtp(Request $request)
 {
  $mobileverification  = Verifysms::where([['phone','=', $request->phone],['otp','=',$request->otp]])->first();
        if($mobileverification){
            Verifysms::where('phone','=',$request->phone)->update(['otp' => null]);
            return response(["status" => 200, "message" => "Success"]);
        }
        else{
            return response(["status" => 401, 'message' => 'Invalid Credentials']);
        }
 }
    }


