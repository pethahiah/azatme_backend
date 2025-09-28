<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Referrals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use Carbon\Carbon;
use App\User;
use App\Kyc;
use Mail;
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




    public $referral;

    public function __construct(Referrals $referral)
    {
        $this->referral = $referral;
    }

/**
 * @group Users Authentication
 *
 * API endpoints for user management.
 *
 * Register a new user.
 *
 * @bodyParam name string required The user's full name. Example: John Doe
 * @bodyParam email string required The user's email address. Example: user@example.com
 * @bodyParam password string required The user's password. Example: yourpassword
 * @bodyParam password_confirmation string required Confirmation of the user's password. Example: yourpassword
 * @bodyParam usertype string required The type of the user. Can be 'user', 'merchant', or 'sponsor'. Example: user
 *
 * @response 201 {
 *   "status": "success",
 *   "data": {
 *     "user": {
 *       "id": 1,
 *       "email": "user@example.com",
 *       "name": "John Doe"
 *     }
 *   },
 *   "message": "Registration successful."
 * }
 *
 * @response 400 {
 *   "status": "error",
 *   "message": "Validation errors.",
 *   "errors": {
 *     "email": ["The email has already been taken."],
 *     "password": ["The passwords do not match."]
 *   }
 * }
 *
 * @response 500 {
 *   "status": "error",
 *   "message": "Server error. Please try again later."
 * }
 *
 * @post /register
 */

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users',
            'usertype' => 'required|string',
            'company_name' => 'string',
            'phone' => 'string|unique:users|required',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => 'required|same:password',
        ]);

        $uniqueCode = $request->unique_code;

        $result = $this->referral->processReferral($uniqueCode, $request->name, $request->email);

        if ($result['success']) {
            Log::info($result['message']);
        } else {
        $errorMessage = isset($result['error']) ? $result['error'] : 'Unknown error occurred';
        Log::error($errorMessage);
    }

             $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'unique_code' => $uniqueCode, // Save unique_code in the user table

        ]);
        $user->save();
        return response()->json(['message' => 'User has been registered', 'data' => $user], 200);
    }


private function getRequesterIP(): ?string
{
    return request()->ip();
}


   
   /**
 * Update the status of an issue.
 *
 * This endpoint allows updating the status of a complaint based on the provided complain reference code.
 * If the reference code is found, the status is updated, and a success message is returned. 
 * Otherwise, an error message is returned indicating that the reference code was not found.
 *
 * @urlParam complain_reference_code string The complain reference code for the issue to be updated. Example: "ABCD1234"
 * @bodyParam status string The new status for the issue. Example: "resolved"
 *
 * @method PUT
 * @endpoint /user/update/status
 *
 * @response {
 *  "message": "Status updated to successfully",
 *  "data": {
 *    "complain_reference_code": "ABCD1234",
 *    "status": "resolved"
 *  }
 * }
 * @response 404 {
 *  "message": "Reference code not found"
 * }
 */
public function updateIssues(Request $request, $complain_reference_code)
{
    $updateIssue = Feedback::where('complain_reference_code', $complain_reference_code)->first();

    if ($updateIssue) {
        // Update the status
        $updateIssue->status = $request->status;
        $updateIssue->save();

        return response([
            'message' => 'Status updated to successfully',
            'data' => $updateIssue
        ], 200);
    } else {
        return response([
            'message' => 'Reference code not found'
        ], 404);
    }
}





    /**
     * Attempt to log in a user with credentials.
     *
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam password string required The user's password. Example: yourpassword
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "token": "jwt_token_here",
     *     "user": {
     *       "id": 1,
     *       "email": "user@example.com",
     *       "name": "John Doe"
     *     }
     *   },
     *   "message": "Login successful."
     * }
     *
     * @response 400 {
     *   "status": "error",
     *   "message": "Invalid credentials."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /AttemptLogin
     */

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

    /**
     * Log in a user using an OTP (One-Time Password).
     *
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam otp string required The OTP sent to the user's email or phone. Example: 123456
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "token": "jwt_token_here",
     *     "user": {
     *       "id": 1,
     *       "email": "user@example.com",
     *       "name": "John Doe"
     *     }
     *   },
     *   "message": "Login successful."
     * }
     *
     * @response 400 {
     *   "status": "error",
     *   "message": "Invalid OTP or email."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /loginViaOtp
     */

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

    /**
     * Log out the currently authenticated user.
     *
     * This endpoint allows the currently authenticated user to log out by revoking their access token. If the user is already logged out or no token exists, an appropriate message is returned.
     *
     * @response 200 {
     *     "status": "success",
     *     "error": false,
     *     "message": "Success! You are logged out."
     * }
     *
     * @response 403 {
     *     "status": "failed",
     *     "error": true,
     *     "message": "Failed! You are already logged out."
     * }
     * @post /logout
     */


    public function logout(): \Illuminate\Http\JsonResponse
    {

        if(Auth::check()) {
        Auth::user()->token()->revoke();
        return response()->json(["status" => "success", "error" => false, "message" => "Success! You are logged out."], 200);
        }
        return response()->json(["status" => "failed", "error" => true, "message" => "Failed! You are already logged out."], 403);
    }

    /**
     * Update the user's profile information.
     *
     * This endpoint allows the authenticated user to update their profile details. The user can update fields such as name, address, and identification numbers. Validation is performed to ensure data integrity.
     *
     * @bodyParam first_name string optional The user's first name. Min: 2, Max: 45 characters.
     * @bodyParam last_name string optional The user's last name. Min: 2, Max: 45 characters.
     * @bodyParam address string optional The user's address.
     * @bodyParam nimc string optional The user's NIMC number. Exactly 11 characters.
     * @bodyParam bvn string optional The user's BVN number. Exactly 11 characters.
     * @bodyParam country string optional The user's country.
     * @bodyParam state string optional The user's state.
     * @bodyParam age string optional The user's age.
     * @bodyParam gender string optional The user's gender.
     * @bodyParam dob string optional The user's date of birth.
     * @bodyParam lga_of_origin string optional The user's local government area of origin.
     * @bodyParam maiden_name string optional The user's maiden name.
     *
     * @response 200 {
     *     "status": "true",
     *     "message": "Profile updated successfully",
     *     "data": {
     *         "first_name": "string",
     *         "last_name": "string",
     *         "middle_name": "string",
     *         "state": "string",
     *         "nimc": "string",
     *         "bvn": "string",
     *         "country": "string",
     *         "address": "string",
     *         "age": "string",
     *         "gender": "string",
     *         "lga_of_origin": "string",
     *         "dob": "string",
     *         "maiden": "string"
     *     }
     * }
     *
     * @response 422 {
     *     "status": "false",
     *     "message": "Validation error message",
     *     "data": []
     * }
     *
     * @response 500 {
     *     "status": "false",
     *     "message": "Internal server error message",
     *     "data": []
     * }
     * @put /updateProfile
     */


    public function updateProfile(Request $request){
        try {
                $validator = Validator::make($request->all(),[
                'first_name' => 'string|min:2|max:45',
//                'middle_name' => 'string',
                'last_name' => 'string|min:2|max:45',
                'address' => 'string',
                'nimc' => 'string|min:11|max:11',
                'bvn' => 'string|min:11|max:11',
                'country' => 'string',
                'state' => 'string',
		        'age' => 'string',
		        'gender' => 'string',
                'dob' => 'string',
		        'lga_of_origin' => 'string',
//		        'maiden'=> 'string',
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
                    $user->dob = $request->dob;
                    $user->maiden = $request->maiden_name;

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

    /**
     * Upload and save a profile image for the authenticated user.
     *
     * This endpoint allows the authenticated user to upload an image, which is then saved to the server's storage directory. The image file must be in one of the allowed formats (jpeg, png, jpg, gif). The image path is then saved in the user's profile.
     *
     * @bodyParam image file required The image file to upload. Must be in jpeg, png, jpg, or gif format.
     *
     * @response 200 {
     *     "image": "http://yourdomain.com/storage/profiles/image_filename.jpg",
     *     "user": {
     *         "id": "integer",
     *         "name": "string",
     *         "email": "string",
     *         "image": "storage/profiles/image_filename.jpg"
     *     }
     * }
     *
     * @response 422 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "image": ["The image field is required.", "The image must be a file of type: jpeg, png, jpg, gif."]
     *     }
     * }
     * @post /image
     */


// public function uploadImage(Request $request)
// {
//     $request->validate([
//         'image' => 'required|image|mimes:jpeg,png,jpg,gif',
//     ]);

//     $user = Auth::user();
//     $path = null;

//     if ($request->hasFile('image') && $request->file('image')->isValid()) {
//         $image = $request->file('image');
//         $path = 'storage/profiles/' . $image->hashName();
//         $image->move(public_path('storage/profiles'), $image->hashName());

//         $user->image = $path;
//     }

//     $user->save();
//     return response()->json(['image' => asset($user->image), 'user' => $user], 200);
// }


public function uploadImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif',
    ]);

    $user = Auth::user();
    $path = null;

    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $image = $request->file('image');
        $filename = $image->hashName();

        // Save image to: storage/app/public/profiles
        $image->move(storage_path('app/public/profiles'), $filename);

        // Store relative path in DB
        $relativePath = 'profiles/' . $filename;
        $user->image = $relativePath;
        $user->save();

        // Manually construct URL like: Azat_pay/storage/app/public/profiles/xyz.jpg
        $manualUrl = url('Azat_pay/storage/app/public/' . $relativePath);

        return response()->json([
            'image' => $manualUrl,
            'user' => $user,
        ], 200);
    }

    return response()->json(['error' => 'Invalid image upload.'], 400);
}



    /**
     * Add a user to the authenticated user's business.
     *
     * This endpoint allows the authenticated user to add another user to their business by updating the user's `company_name` to match the business name of the authenticated user.
     *
     * @bodyParam id int required The ID of the user to be added to the business.
     *
     * @response 200 {
     *     "status": "true",
     *     "message": "User added successfully"
     * }
     *
     * @response 404 {
     *     "status": "false",
     *     "message": "User not found",
     *     "data": []
     * }
     *
     * @response 500 {
     *     "status": "false",
     *     "message": "Failed to update user",
     *     "data": []
     * }
     */


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
    /**
     *
     * Remove a user from the authenticated user's business.
     *
     * This endpoint allows the authenticated user to remove another user from their business by clearing the user's `company_name`. This action is only authorized for users with the `merchant` role.
     *
     * @bodyParam id int required The ID of the user to be removed from the business.
     *
     * @response 200 {
     *     "status": "true",
     *     "message": "Profile updated successfully"
     * }
     *
     * @response 403 {
     *     "status": "false",
     *     "message": "User is not a merchant, so you are not authorized to perform this action"
     * }
     *
     * @response 404 {
     *     "status": "false",
     *     "message": "User not found",
     *     "data": []
     * }
     *
     * @response 500 {
     *     "status": "false",
     *     "message": "Failed to update user profile",
     *     "data": []
     * }
     */


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

    /**
     *
     * List all users associated with the authenticated user's business.
     *
     * This endpoint retrieves a list of all users who are associated with the authenticated user's business. It filters the users by the `company_name` of the authenticated user and ensures that only users with the `user` role are listed. This action is only authorized for users with the `merchant` role.
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "id": 1,
     *             "name": "John Doe",
     *             "email": "john.doe@example.com",
     *             "company_name": "Business XYZ",
     *             "usertype": "user",
     *             "created_at": "2024-08-18T00:00:00.000000Z",
     *             "updated_at": "2024-08-18T00:00:00.000000Z"
     *         },
     *         // Other users
     *     ]
     * }
     *
     * @response 403 {
     *     "status": "false",
     *     "message": "User is not a merchant, so you are not authorized to perform this action"
     * }
     */


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

    /**
     * @group User Profile
     *
     * Retrieve the profile of the authenticated user.
     *
     * This endpoint returns the profile details of the currently authenticated user. It retrieves user information based on the authenticated user's ID.
     *
     * @response 200 {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "usertype": "user",
     *     "company_name": "Business XYZ",
     *     "phone": "1234567890",
     *     "password": "hashed_password",
     *     "unique_code": "ABC123",
     *     "created_at": "2024-08-18T00:00:00.000000Z",
     *     "updated_at": "2024-08-18T00:00:00.000000Z",
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "middle_name": "M",
     *     "address": "123 Main St",
     *     "nimc": "12345678901",
     *     "bvn": "12345678901",
     *     "country": "Country Name",
     *     "state": "State Name",
     *     "age": "30",
     *     "gender": "Male",
     *     "dob": "1994-01-01",
     *     "lga_of_origin": "Local Government Area",
     *     "maiden": "Maiden Name",
     *     "image": "storage/profiles/user_image.jpg"
     * }
     *
     * @response 401 {
     *     "status": "error",
     *     "message": "Unauthorized"
     * }
     *
     * @get /getProfile
     */


    public function getProfile()
    {
        $id = Auth::user();
        $getProfileFirstt = user::where('id', $id->id)->get();
        return response()->json($getProfileFirstt);

    }

    /**
     * Retrieve a list of all users with their IDs and emails.
     *
     * This endpoint returns a list of all users, including only their ID and email. It is typically used for administrative or user management purposes.
     *
     * @response 200 {
     *     [
     *         {
     *             "id": 1,
     *             "email": "john.doe@example.com"
     *         },
     *         {
     *             "id": 2,
     *             "email": "jane.doe@example.com"
     *         }
     *     ]
     * }
     *
     * @response 500 {
     *     "message": "Internal Server Error"
     * }
     */


    public function getAllUser()
    {

        $user = user::select('id', 'email')->get();
        return response()->json($user);

    }


public function updateUserEmailStatus(Request $request)
{
   $allowedEmails = [
    'adunola.adeyemi@gmail.com',
    'akm@mailinator.com',
    'sunday4oged@yahoo.com',
    'ade_adun@yahoo.com',
    'azatme@mailinator.com',
    'lumiged4u@gmail.com',
'lumiged4u+dummy002@gmail.com',
'lumiged4u@+dummy003@gmail.com',
'lumiged4u@+dummy004@gmail.com',
'lumiged4u+dummy005@gmail.com',
'lumiged4u+dummy006@gmail.com',
    'adeyemiglr@gmail.com',
    'akm1@mailinator.com',
    'emaevidence@gmail.com',
    'emmanuelalabi563@gmail.com',
];
    $email = $request->input('email');

    if (in_array($email, $allowedEmails)) {
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




    /**
     *
     * Retrieve and verify BVN details for the authenticated user.
     *
     * This endpoint retrieves BVN details using an external service and verifies them against the authenticated user's details.
     * If the details match, it updates the user's verification status.
     *
     * @bodyParam accessToken string required The access token for the external service. Example: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c`
     *
     * @response 200 {
     *     "user": {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john.doe@example.com",
     *         "isVerified": 1
     *     },
     *     "message": "User details updated successfully"
     * }
     *
     * @response 400 {
     *     "error": "Mismatch in data. Update aborted."
     * }
     *
     * @response 500 {
     *     "error": "Failed to get BVN details"
     * }
     *
     * @response 500 {
     *     "error": "Failed to decode JSON response"
     * }
     */


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


private function areUserDetailsMatching($user, $validationData): bool
{
    // Log the validation data
    Log::info('Validation Data:', [
        'surname' => $validationData['surname'] ?? null,
        'first_name' => $validationData['first_name'] ?? null,
        'dateOfBirth' => $validationData['dateOfBirth'] ?? null,
        'gender' => $validationData['gender'] ?? null
    ]);

    // Compare the surname or maiden name
    $surnameMatch = strtoupper($user->last_name) === strtoupper($validationData['surname']);
    $maidenMatch = false;
    if (!$surnameMatch && isset($user->maiden)) {
        $maidenMatch = strtoupper($user->maiden) === strtoupper($validationData['surname']);
        if ($maidenMatch) {
            $surnameMatch = true;
        }
    }

    // Convert and compare the dates
    $dobMatch = isset($validationData['dateOfBirth']) &&
        date('Y-m-d', strtotime($user->DOB)) === date('Y-m-d', strtotime($validationData['dateOfBirth']));

    // Compare gender
    $genderMatch = isset($validationData['gender']) && (
        strtoupper($user->gender) === strtoupper($validationData['gender']) ||
        ucfirst(strtolower($user->gender)) === ucfirst(strtolower($validationData['gender']))
    );

    // Log the user data with match statuses
    Log::info('User Data:', [
        'last_name_match' => $surnameMatch,
        'first_name_match' => strtoupper($user->first_name) === strtoupper($validationData['first_name']),
        'maiden_name_match' => $maidenMatch,
        'dob' => $user->DOB,
        'gender_match' => $genderMatch
    ]);

    // Return the final result of matching
    return (
        $surnameMatch &&
        isset($validationData['first_name']) &&
        strtoupper($user->first_name) === strtoupper($validationData['first_name']) &&
        $genderMatch &&
        $dobMatch
    );
}





    /**
     *
     * Update the user type for the authenticated user.
     *
     * This endpoint allows updating the user type of the authenticated user, but prevents setting it to "admin."
     *
     * @bodyParam usertype string required The new user type for the user. Example: `merchant`
     *
     * @response 200 {
     *     "success": true,
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "usertype": "merchant"
     * }
     *
     * @response 403 {
     *     "error": "You cannot update the usertype to admin."
     * }
     *
     * @response 404 {
     *     "error": "User not found."
     * }
     */


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

	// Check if the user has provided KYC; if not, prevent the update
    $kyc = Kyc::where('user_id', $user->id)->first();
    if (!$kyc) {
        return response()->json(['error' => 'You must have previously  provided KYC before switching user type.'], 403);
    }

    // Update the usertype
    $user->usertype = $request->usertype;
    $user->saveOrFail();

    return response()->json(['success' => true, $user]);
}

    /**
     * Initiates the BVN consent process.
     *
     * This endpoint sends a request to the BVN consent service, generates a unique request ID, and computes a hash for verification.
     * It returns a URL where the user can provide their BVN consent.
     *
     * @response 200 {
     *     "requestId": "2024081812345678901234",
     *     "responseData": {
     *         "data": {
     *             "url": "https://example.com/redirect/2024081812345678901234"
     *         },
     *         "status": true,
     *         "message": "Success"
     *     }
     * }
     *
     * @response 400 {
     *     "error": {
     *         "message": "Failed to initiate BVN consent.",
     *         "code": "API_ERROR"
     *     }
     * }
     */


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

    /**
     * Retrieves BVN consent details based on a previously initiated consent request.
     *
     * This endpoint first calls the `initiateBvnConsent` method to get a `requestId`. It then uses this `requestId` to fetch the BVN consent details.
     * The response contains the details of the BVN consent or an error if the request fails.
     *
     * @response 200 {
     *     "data": {
     *         "status": true,
     *         "message": "Consent retrieved successfully",
     *         "consentDetails": {
     *             // Include details here as per the response structure from the external service
     *         }
     *     }
     * }
     *
     * @response 500 {
     *     "error": {
     *         "message": "Failed to get BVN consent.",
     *         "code": "API_ERROR"
     *     }
     * }
     *
     * @queryParam requestId string The request ID obtained from initiating the BVN consent process. Example: 2024081812345678901234
     */


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



public function updateUserEmailStatusToUnverify(Request $request)
{
    $email = $request->input('email');
    
    $allowedEmails = [
        'adunola.adeyemi@gmail.com',
        'akm@mailinator.com',
        'sunday4oged@yahoo.com',
        'ade_adun@yahoo.com',
        'azatme@mailinator.com',
        'lumiged4u@gmail.com',
        'adunola.adeyemi+ref001@gmail.com',
        'adunola.adeyemi+ref002@gmail.com',
        'adunola.adeyemi+ref003@gmail.com',
        'adunola.adeyemi+ref004@gmail.com',
        'adunola.adeyemi+ref005@gmail.com',
        'adunola.adeyemi+ref006@gmail.com',
        'adunola.adeyemi+ref007@gmail.com',
        'adunola.adeyemi+ref008@gmail.com',
        'adeyemiglr@gmail.com',
        'adunola.adeyemi+ref009@gmail.com',
        'adunola.adeyemi+ref010@gmail.com'
    ];

    if (in_array($email, $allowedEmails)) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['isVerified' => 0]);
            return response()->json(['message' => 'User Verified'], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    } else {
        return response()->json(['message' => 'Access denied'], 403);
    }
}


public function getAllMerchants(Request $request): \Illuminate\Http\JsonResponse
{
    $perPage = (int) $request->query('perPage', 10);
    $page = (int) $request->query('page', 1);

    // Retrieve paginated list of merchants
    $merchants = User::query()
        ->where('usertype', 'merchant')
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'status' => 'success',
        'data' => [
            'users' => $merchants->items(),
            'pagination' => [
                'current_page' => $merchants->currentPage(),
                'total_pages' => $merchants->lastPage(),
                'total_users' => $merchants->total(),
                'per_page' => $merchants->perPage(),
            ],
        ],
    ], 200);
}

public function getAllSponsors(Request $request): \Illuminate\Http\JsonResponse
{
    $perPage = (int) $request->query('perPage', 10);
    $page = (int) $request->query('page', 1);

    // Retrieve paginated list of sponsors
    $sponsors = User::query()
        ->where('usertype', 'sponsor')
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'status' => 'success',
        'data' => [
            'users' => $sponsors->items(),
            'pagination' => [
                'current_page' => $sponsors->currentPage(),
                'total_pages' => $sponsors->lastPage(),
                'total_users' => $sponsors->total(),
                'per_page' => $sponsors->perPage(),
            ],
        ],
    ], 200);
}

public function getMerchantById(Request $request, int $id): \Illuminate\Http\JsonResponse
{
    // Find the merchant by ID and ensure the user type is 'merchant'
    $merchant = User::where('id', $id)
        ->where('usertype', 'merchant')
        ->first();

    if (!$merchant) {
        return response()->json([
            'status' => 'error',
            'message' => 'Merchant not found.',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $merchant,
    ], 200);
}

public function getAuthMerchantDetails(Request $request): \Illuminate\Http\JsonResponse
{
    // Get the authenticated user
    $merchant = auth()->user();

    // Ensure the authenticated user is a merchant
    if (!$merchant || $merchant->usertype !== 'merchant') {
        return response()->json([
            'status' => 'error',
            'message' => 'Authenticated merchant not found or user is not a merchant.',
        ], 404);
    }

    // Fetch associated KYC details
    $kycDetails = $merchant->kyc; // Assuming a relationship 'kycDetails' exists in the User model

    return response()->json([
        'status' => 'success',
        'data' => [
            'merchant' => $merchant,
            'kyc_details' => $kycDetails,
        ],
    ], 200);
}

public function getAuthSponsorDetails(Request $request): \Illuminate\Http\JsonResponse
{
    // Get the authenticated user
    $merchant = auth()->user();

    // Ensure the authenticated user is a merchant
    if (!$merchant || $merchant->usertype !== 'sponsor') {
        return response()->json([
            'status' => 'error',
            'message' => 'Authenticated merchant not found or user is not a merchant.',
        ], 404);
    }

    // Fetch associated KYC details
    $kycDetails = $merchant->kyc; // Assuming a relationship 'kycDetails' exists in the User model

    return response()->json([
        'status' => 'success',
        'data' => [
            'sponsor' => $merchant,
            'kyc_details' => $kycDetails,
        ],
    ], 200);
}


}
