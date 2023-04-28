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
use App\Http\Requests\BusinessRequest;
use App\Business;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    
     //Register

     public function register(Request $request){
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'usertype' => 'required|string',
            'company_name' => 'string',
            'phone' => 'string|unique:users|required',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => '|required|same:password',
        ]);


        $users = User::where('email', $request->email)->get();
        
        if(sizeof($users) > 0){
            // tell user not to duplicate same email
            return response([
                'message' => 'user already exists'
            ], 409);
        }
   
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'company_name' => $request->company_name,
            'phone'=> preg_replace('/^0/','+234',$request->phone),
            'password' => Hash::make($request->password)
        
        ]);
        $user->save();
        return response()->json(['message' => 'user has been registered', 'data'=>$user], 200);       
}




//login function

    public function AttemptLogin(LoginRequest $request)
    {
        $otp = random_int(0, 999999);
        $otp = str_pad($otp, 6, 0, STR_PAD_LEFT);
        Log::info("otp = ".$otp);
        
        $email = request()->get('email');
        $password = request()->get('password');
        $uxer = User::where('email', '=', $email)->first();
        if(Hash::check($request->password, $uxer->password))
        {
            $user = User::where('phone', $email)->orWhere('email', $email)->update(['otp' => $otp]);
            //send email
           // $data =  ['otp' => $otp];
            $data = [
           'otp' => $otp,
           'email' => $email
];
            $subject = 'AzatMe: ONE TIME PASSWORD';
            Mail::send('Email.otp', $data, function($message) use($request,$subject){
                $message->to($request->email)->subject($subject);
            });
            return response(["status" => 200, "message" => "OTP sent successfully"]);
            
    }else{   
        

        return response()->json([
            'message' => 'Record not found.'
        ], 404);
       // return response()->json(['status'=>'false','message'=>'password is not correct']);
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
                'middle_name' => 'string',
                'last_name' => 'string|min:2|max:45',
                'address' => 'string',
                'nimc' => 'string|min:11|max:11',
                'bvn' => 'string|min:11|max:11',
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
                    $user->middle_name = $request->middle_name;
                    $user->state = $request->state;
                    $user->nimc = $request->nimc;  
                    $user->bvn = $request->bvn;
                    $user->country = $request->country;
                    $user->address = $request->address;
                   
                    // $user->image = $request->image;
                   //return $user;
                            $user->update();
                             return response()->json(['status'=>'true', 'message'=>"profile updated suuccessfully", 'data'=>$user]);
                
                }
    
        }catch (\Exception $e){
                    return response()->json(['status'=>'false', 'message'=>$e->getMessage(), 'data'=>[]], 500);
        }
    }
    
    public function uploadImage(Request $request)
    {
        $generalPath = env('file_public_path');
       // return response()->json($request->input());
       
       $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
   
       $user = Auth::user();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads'), $imageName);
            $path = "$generalPath/$imageName";
            $user->image = $path;
            $user->save();
            return response()->json(['success' => true, 'image_url' => $path]);
        } else {
            return response()->json(['success' => false, 'message' => 'Image not found']);
        }
                             
    }


    public function addUsersToBusiness(Request $request, $id)
    {
        $getBusiness = Auth::user();
        $get_user = User::where('id',$id)->first();
        
        $updated = $get_user->fill(['company_name' => $getBusiness->company_name])->save();
          
          if ($updated){
            
            return response()->json(['status'=>'true', 'message'=>"User added successfully"]);
          }else {
            return response()->json(['status'=>'false', 'data'=>[]]);
        }
    }


    public function removeUsersFromABusiness(Request $request, $id)
    {
        $getBusiness = Auth::user()->usertype;
        $get_user = User::where('id',$id)->first();
        if($getBusiness == 'merchant')
        {
            $get_user->update(['company_name' => Null]);
            return response()->json(['status'=>'true', 'message'=>"profile updated successfully"]);
            
            
        }else{
            return response()->json('User is not a merchant, so you are not authorize to perform this action');
                    } 
       
    }

    public function listAllBusinessUsers()
    {
        $Business = Auth::user();
        $getBusiness = $Business -> usertype;
            if($getBusiness === 'merchant')
                {
                    $getAllBusinessUsers = User::where('company_name', $Business->company_name)->where('usertype', 'user')->get();
         return response()->json($getAllBusinessUsers);
                }
            else{
        return response()->json('User is not a merchant, so you are not authorize to perform this action');
                }   
    }



    public function getProfile()
    {
        $id = Auth::user();
        $getProfileFirstt = user::where('id', $id->id)->get();
        return response()->json($getProfileFirstt);

    }

    public function getAllUser()
    {
      
        $user = user::select('id', 'email')->get();
        return response()->json($user);

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


    public function signin()
{
      $current_timestamp= now();
      $timestamp = strtotime($current_timestamp);
     // echo $timestamp;
      $secret = env('PayThru_App_Secret');
      $hash = hash('sha256', $secret . $timestamp);
      $PayThru_AppId = env('PayThru_ApplicationId');
      $TestUrl = env('PayThru_Base_Test_Url');
      $data = [
        'ApplicationId' => "93cdbd1e3ae649b3b5e173ffb87d95d2993de430b81a4415b8c2f309356d2278",
        'password' => $hash
      ];
      
      //return $data;
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Timestamp' => $timestamp,
  ])->post('https://services.paythru.ng/identity/auth/login', $data);
    //return $response;
    if($response->Successful())
    {
      $banks = json_decode($response->body(), true);
      return response()->json($banks);
    }
}
    
    
    
   
   

}
