<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use Carbon\Carbon;
use App\User;
use Mail;
use Log;


class AuthController extends Controller
{
    
     //Register

     public function register(Request $request){
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'usertype' => 'string',
            'phone' => 'string|unique:users',
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => '|required|same:password',
        ]);

        $users = User::where('email', $request->email)->get();
        
        if(sizeof($users) > 0){
            // tell user not to duplicate same email
            return response([
                'message' => 'user already exists'
            ], 401);
        }
   

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'phone'=> preg_replace('/^0/','+234',$request->phone),
            'password' => Hash::make($request->password)
        ]);
        $user->save();
        return response()->json(['message' => 'user has been registered', 'data'=>$user], 200);       
}

//login function

    public function AttemptLogin(LoginRequest $request)
    {
        $otp = rand(1000,9999);
        Log::info("otp = ".$otp);
        
        $email = request()->get('email');
        $password = request()->get('password');

       //$this->validate($request, [
       
    //]);

        $user = User::where('phone', $email)->orWhere('email', $email)->update(['otp' => $otp]);

        // $user = User::where(['email','=',$request->email], ['password','=',$request->password])->update(['otp' => $otp]);
        //if($user) 
        
        //return $user= $request->password;
      if (is_null(($user) || ($password)))
        {  
            
             return response(["status" => 401, 'message' => 'Invalid']); 
       
           
        }else{

            //send email
            $data =  ['otp' => $otp];
            $subject = 'AzatMe: ONE TIME PASSWORD';
            Mail::send('Email.otp', $data, function($message) use($request,$subject){
                $message->to($request->email)->subject($subject);
            });
            return response(["status" => 200, "message" => "OTP sent successfully"]);
         
        }

    }

    public function loginViaOtp(Request $request)
    {

    $user  = User::where([['email','=',$request->email],['otp','=',$request->otp]])->first();
        if($user){
            auth()->login($user, true);
            User::where('email','=',$request->email)->update(['otp' => null]);
            $accessToken = auth()->user()->createToken('authToken')->accessToken;

            return response(["status" => 200, "message" => "Success", 'user' => auth()->user(), 'access_token' => $accessToken]);
        }
        else{
            return response(["status" => 401, 'message' => 'Invalid']);
        }

        
    }
    //logout function

    public function logout() {

        if(Auth::check()) {
        Auth::user()->token()->revoke();
        return response()->json(["status" => "success", "error" => false, "message" => "Success! You are logged out."], 200);
        }
        return response()->json(["status" => "failed", "error" => true, "message" => "Failed! You are already logged out."], 403);
    }

    public function updateProfile(Request $request){
        try {
                $validator = Validator::make($request->all(),[
                'first_name' => 'string|min:2|max:45',
                'last_name' => 'string|min:2|max:45',
                'address' => 'string',
                'country' => 'string',
                'state' => 'string'
               
            ]);
                if($validator->fails()){
                    $error = $validator->errors()->all()[0];
                    return response()->json(['status'=>'false', 'message'=>$error, 'data'=>[]],422);
                }else{
                    $user = user::find($request->user()->id);
                    $user->first_name = $request->first_name;
                    $user->last_name = $request->last_name;
                    $user->state = $request->state;
                    $user->country = $request->country;
                    $user->address = $request->address;
                    
                   //return $user;
                            $user->update();
                            //return response()->json($user);
                             return response()->json(['status'=>'true', 'message'=>"profile updated suuccessfully", 'data'=>$user]);
                }
    
        }catch (\Exception $e){
                    return response()->json(['status'=>'false', 'message'=>$e->getMessage(), 'data'=>[]], 500);
        }
    }

    public function getProfile(){
        $id = Auth::user();
        $getProfileFirstt = user::where('id', $id->id)->get();
        return response()->json($getProfileFirstt);

    }

    public function updateUsertype(Request $request)
    {
    $id = Auth::user();
    //return $id;
    $user = User::where('id', $id->id)->firstOrFail(); 
    $user->usertype = $request->usertype;
    //return $request->usertype;
    $user->saveOrFail();
    return response()->json(['success' => true, $user]);
    }
   

}
