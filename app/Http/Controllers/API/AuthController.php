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
use Illuminate\Validation\Rule;
use App\Http\Requests\BusinessRequest;
use App\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\QueryException;
//use App\Services\PaythruService;
use Illuminate\Support\Facades\DB;



class AuthController extends Controller
{

     //Register


     public function register(Request $request){
        $this->validate($request, [
        'name' => 'required|min:3|max:50',
        'email' => 'required|email|unique:users',
        'usertype' => 'required|string',
        'company_name' => 'string',
        'phone' => 'string|unique:users|required',
        'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
        'password_confirmation' => 'required|same:password',
    ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'company_name' => $request->company_name,
            'phone'=> $request->phone,
            'password' => Hash::make($request->password)
        ]);
        $user->save();
        return response()->json(['message' => 'user has been registered', 'data'=>$user], 200);
}

    //login function

public function getRequesterIP()
{
    return request()->ip();
}

public function AttemptLogin(LoginRequest $request)
{
try {
    $email = $request->get('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['message' => 'Invalid email format.'], 422);
    }
    $password = $request->get('password');
    $user = User::where('email', '=', $email)->first();

    // Implement rate limiting for user and IP address using Redis cache
//    $userRateLimitKey = 'rate_limit:user:' . $user->id . ':' . $email;
//    $ipRateLimitKey = 'rate_limit:ip:' . $this->getRequesterIP();
    $rateLimitDuration = 300; // 5 minutes
    $rateLimitMaxAttempts = 3;

//    $currentUserAttempts = (int) Redis::get($userRateLimitKey) ?? 0;
//    $currentIPAttempts = (int) Redis::get($ipRateLimitKey) ?? 0;

//    if ($currentUserAttempts >= $rateLimitMaxAttempts || $currentIPAttempts >= $rateLimitMaxAttempts) {
  //      return response(['message' => 'Too many login attempts. Try again after 5 minutes.'], 429);
   // }

    if (Hash::check($password, $user->password)) {
        $otp = random_int(0, 999999);
        $otp = str_pad($otp, 6, 0, STR_PAD_LEFT);
        Log::info("otp = " . $otp);

        // Update user's OTP in the database
        $user->otp = $otp;
        $user->save();

        // Send email with OTP
        $data = [
            'otp' => $otp,
            'email' => $email
        ];
        $subject = 'AzatMe: ONE TIME PASSWORD';
        Mail::send('Email.otp', $data, function ($message) use ($request, $subject) {
            $message->to($request->email)->subject($subject);
        });

        // Ensure to update the Redis keys when the login is successful
//        Redis::incr($userRateLimitKey);
//        Redis::incr($ipRateLimitKey);
//        if (!Redis::ttl($userRateLimitKey)) {
//            Redis::expire($userRateLimitKey, $rateLimitDuration);
//        }
//        if (!Redis::ttl($ipRateLimitKey)) {
//            Redis::expire($ipRateLimitKey, $rateLimitDuration);
//        }

        return response(["status" => 200, "message" => "OTP sent successfully"]);
    } else {
        return response()->json(['message' => 'Record not found.'], 404);
    }
} catch (QueryException $e) {
        // Handle database query exceptions
        return response(['message' => 'Database Error'], 500);
    } catch (\Exception $e) {
        // Handle other exceptions
        return response(['message' => 'Internal Server Error'], 500);
    }

}

public function loginViaOtp(Request $request)
{
    $user = User::where([['email', '=', $request->email], ['otp', '=', $request->otp]])->first();
    if ($user) {
        auth()->login($user, true);
        $user->otp = null;
        $user->save();
        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        // Reset the rate limit counters after successful login
//        $userRateLimitKey = 'rate_limit:user:' . $user->id . ':' . $request->email;
//        $ipRateLimitKey = 'rate_limit:ip:' . $this->getRequesterIP();
//        Redis::del($userRateLimitKey);
//        Redis::del($ipRateLimitKey);

        // Get and display the user data from Redis cache
       // $userDataKey = 'user:' . $user->id;
       // $userData = Redis::get($userDataKey);

//        if ($userData) {
            // Assuming user data was stored as JSON, decode it to an array for display
  //          $userArray = json_decode($userData, true);

            // Display the user data as needed
            // For example:
    //        echo "User ID: " . $userArray['id'] . "<br>";
      //      echo "Name: " . $userArray['name'] . "<br>";
            // and so on...
       // }

        return response(["status" => 200, "message" => "Success", 'user' => auth()->user(), 'access_token' => $accessToken]);
    } else {
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
                'state' => 'string',
		'age' => 'string',
		'gender' => 'string',
		'lga_of_origin' => 'string',
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
                    $user->age = $request->age;
                    $user->gender = $request->gender;
                    $user->lga_of_origin = $request->lga_of_origin;
                    // $user->image = $request->image;
                   //return $user;
                            $user->update();
                             return response()->json(['status'=>'true', 'message'=>"profile updated suuccessfully", 'data'=>$user]);

                }

        }catch (\Exception $e){
                    return response()->json(['status'=>'false', 'message'=>$e->getMessage(), 'data'=>[]], 500);
        }
    }

	public function uploadImagennn(Request $request)
{
   $request->validate([
       'image' => 'required|image|mimes:jpeg,png,jpg,gif',
   ]);

    $user = Auth::user();

  $path = null;
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $image = $request->file('image');
        $base64Image = base64_encode(file_get_contents($image->path()));
        $user->image = 'data:' . $image->getMimeType() . ';base64,' . $base64Image;
        $path = public_path('storage/profiles/' . $image->hashName());
    }

	$user->save();
    return response()->json(['image' => $user->image, 'user' => $user], 200);
}


public function uploadImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif',
    ]);

    $user = Auth::user();
    $path = null;

    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $image = $request->file('image');
        $path = 'storage/profiles/' . $image->hashName();
        $image->move(public_path('storage/profiles'), $image->hashName());

        $user->image = $path;
    }

    $user->save();
    return response()->json(['image' => asset($user->image), 'user' => $user], 200);
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


public function updateUserEmailStatus(Request $request)
    {
        $email = $request->input('email');

        if ($email === 'adunola.adeyemi@gmail.com' || $email === 'akm@mailinator.com' || $email === 'sunday4oged@yahoo.com' || $email === 'ade_adun@yahoo.com'|| $email === 'azatme@mailinator.com' || $email === 'lumiged4u@gmail.com') {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update(['isVerified' => 1]);
                return response()->json(['message' => 'User Verified'], 200);
            } else {
                return response()->json(['message' => 'User not found'], 404);
            }
        } else {
             return response()->json(['message' => 'Access denied'], 403);
}
    }






public function getBVNDetails(Request $request)
{
    $accessToken = 'Bearer ' . $request->accessToken;
    Log::info("Token: " . $accessToken);

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => $accessToken,
    ])->get("https://services.paythru.ng/bvn/api/v1/bvn/get-bvn-details");

    Log::info("BVN Response: " . $response->body());

    if (!$response->successful()) {
        Log::error("Failed to get BVN details. Status code: " . $response->status());
        return response()->json(['error' => 'Failed to get BVN details'], 500);
    }

    $dataArray = json_decode($response->body(), true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if ($dataArray['status']) {
            $validationData = $dataArray['data']['validationDataRes'][0];
            $user = Auth::user();

            if ($this->areUserDetailsMatching($user, $validationData)) {
                // Update the user table
                $user->update(['isVerified' => 1]);

                return response()->json(['user' => $user, 'message' => 'User details updated successfully']);
            } else {
                return response()->json(['error' => 'Mismatch in data. Update aborted.'], 400);
            }
        } else {
            return response()->json(['error' => 'Status is not true. Update aborted.'], 400);
        }
    } else {
        return response()->json(['error' => 'Failed to decode JSON response'], 500);
    }
}



private function areUserDetailsMatching($user, $validationData)
{
    return (
        isset($validationData['surname']) &&
        isset($validationData['first_name']) &&
        strtoupper($user->last_name) === $validationData['surname'] &&
        strtoupper($user->first_name) === $validationData['first_name'] &&
        ucfirst($user->gender) === $validationData['gender']
    );
}





   public function updateUsertype(Request $request)
{
    $id = Auth::user();
    $user = User::where('id', $id->id)->firstOrFail();

    // Validate the request, ensuring usertype is not admin
    $this->validate($request, [
        'usertype' => ['required', 'string', Rule::notIn(['admin'])],
    ]);

    // Check if the current user type is admin, and prevent the update
    if ($user->usertype === 'admin') {
        return response()->json(['error' => 'You cannot update the usertype to admin.'], 403);
    }

    // Update the usertype
    $user->usertype = $request->usertype;
    $user->saveOrFail();

    return response()->json(['success' => true, $user]);
}


 public function initiateBvnConsent(Request $request)
    {

        $PayThru_AppId = env('PayThru_ApplicationId');
        $redirectUrl = "https://azatme.com/dashboard/profile/bvnverification/redirect";
        $currentDateTime = now()->format('YmdHisu');
        $randomDigits = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $requestId = $currentDateTime . $randomDigits;

        $secretKey = env('PayThru_App_Secret');
        $concatenatedString = $redirectUrl . $secretKey;
        $computedHash = hash('sha512', $concatenatedString);
        $url = env('igreeServices');

        $data = [
            'ApiKey' => $PayThru_AppId,
            'RequestId' => $requestId,
            'RedirectUrl' => $redirectUrl,
            'Sign' => $computedHash,
        ];
	 Log::info("BVN Data: " . json_encode($data));
//	return response()->json(['success' => true, $data]);
     //echo $data;
        $response = Http::get($url, $data);
//        return $response;
        $responseData = $response->json();

        if ($response->successful()) {
            $urlParts = explode('/', $responseData['data']['url']);
            $urlRequestId = end($urlParts);
            return [
                'requestId' => $urlRequestId,
                'responseData' => $responseData,
            ];
        }
        else {
            return [
                'error' => [
                    'message' => 'Failed to initiate BVN consent.',
                    'code' => 'API_ERROR',
                ],
            ];
        }
    }



    public function getBvnConsent(Request $request)
    {
        $responseFromFirstCall = $this->initiateBvnConsent($request);

        if (isset($responseFromFirstCall['error'])) {
            return response()->json($responseFromFirstCall['error'], 500);
        }
       $requestId = $responseFromFirstCall['requestId'];
        $response = Http::get("https://www.sandbox.paythru.ng/BvnIgree/api/v1/bvn/consent/{$requestId}");
       // return $response;
        if ($response->successful()) {

          return response()->json($response);

        } else {
            // Error: handle the error message and code
            return response()->json([
                'error' => [
                    'message' => 'Failed to get BVN consent.',
                    'code' => 'API_ERROR',
                ],
            ], 500);
        }
    }






}
