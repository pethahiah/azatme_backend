<?php

namespace App\Http\Controllers\API;


use App\Charge;
use App\DirectDebitProduct;
use App\Http\Controllers\Controller;
use App\ReferralSetting;
use App\Services\ChargeService;
use App\Services\Referrals;
use Illuminate\Http\Request;
use App\Services\PaythruService;
use App\Services\PaymentLinkService;
use App\Invitation;
use App\Bank;
use App\Ajo;
use Illuminate\Support\Facades\Mail;
use App\Decline;
use App\User;
use Auth;
use App\PaymentDate;
use App\AjoBalanace;
use App\OpenActive;
use App\AjoContributor;
use Illuminate\Support\Str;
use App\Mail\MyEmail;
use DB;
use App\Active;
use Illuminate\Support\Facades\Http;
use App\Mail\PaymentLinkMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\AjopaymentSent;
use App\AjoWithdrawal;


class AjoController extends Controller
{
    //

    public $paymentLinkService;
    public $referral;
    public $chargeService;


    public function __construct(PaythruService $paythruService, PaymentLinkService $paymentLinkService, Referrals $referral, ChargeService $chargeService)
    {
        $this->paythruService = $paythruService;
        $this->paymentLinkService = $paymentLinkService;
        $this->referral = $referral;
        $this->chargeService = $chargeService;
    }

    /**
     * @group Ajo
     *
     * API endpoints for ajo functionalities.
     */

    /**
     * Retrieve transaction data for an Ajo contributor.
     *
     * @urlParam transactionReference string required The transaction reference. Example: "ABC123"
     * @urlParam email string required The email of the contributor. Example: "user@example.com"
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "transaction_id": "TX123",
     *     "amount": 1000,
     *     "status": "completed"
     *   },
     *   "message": "Transaction data retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Transaction or contributor not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-ajo-contributor/{transactionReference}/{email}
     */


    public function getTransactionData(Request $request, $transactionReference, $email)
{
    $perPage = $request->input('per_page', 10);
    $check = Invitation::where('merchantReference', $transactionReference)
        ->where('email', $email)
        ->first();

    if ($check) {
        $data = Invitation::join('ajo_contributors', 'invitations.merchantReference', '=', 'ajo_contributors.transactionReference')
            ->where('ajo_contributors.transactionReference', $transactionReference)
            ->orderBy('ajo_contributors.created_at', 'desc')
             ->paginate($perPage, ['invitations.merchantReference','ajo_contributors.*']);
    } else {
        $data = AjoContributor::where('transactionReference', $transactionReference)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    return response()->json($data);
}


    /**
     * @group Ajo
     *
     * Retrieve Ajo data by Ajo ID.
     *
     * This endpoint retrieves details of an Ajo entry based on the provided Ajo ID. If the data is found, it returns the details along with a success message. If no data is found, it returns a not found message.
     *
     * @urlParam ajoId string required The unique ID of the Ajo entry. Example: AJO123456
     *
     * @response 200 {
     *     "message": "successful",
     *     "data": [
     *         {
     *             "ajo_id": "AJO123456",
     *             "transactionReference": "TXN123456",
     *             "email": "user@example.com",
     *             "amount": 500,
     *             "status": "active",
     *             "created_at": "2024-08-18T00:00:00.000000Z",
     *             "updated_at": "2024-08-18T00:00:00.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "message": "data not found"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     * @get /get-ajo-by-id/{id}
     */
public function getAjoByIdd(Request $request, $ajoId)
{
//    $user = Auth::user()->email;
    $ajo = Invitation::where('ajo_id', $ajoId)->get();

    if ($ajo) {
        return response()->json(['message' => 'successful', 'data' => $ajo], 200);
    } else {
        return response()->json(['message' => 'data not found'], 404);
    }
}


    /**
     * @group Ajo
     *
     * Retrieve Ajo contributors by Ajo ID.
     *
     * This endpoint retrieves Ajo contributors for a specified Ajo ID. It first checks if there are invitations associated with the given Ajo ID. For each invitation, it retrieves Ajo contributors linked by `merchantReference`, groups them by `transactionReference`, and includes the associated email. It returns the grouped data along with a success message. If no data is found for the specified Ajo ID, it returns a not found message.
     *
     * @urlParam ajo_id string required The unique ID of the Ajo entry. Example: AJO123456
     * @queryParam per_page int optional The number of items per page. Default is 10. Example: 15
     *
     * @response 200 {
     *     "message": "AjoContributors retrieved successfully",
     *     "data": {
     *         "MERCHANT_REF_1": {
     *             "transactionReference": "MERCHANT_REF_1",
     *             "email": "user1@example.com",
     *             "ajoContributors": [
     *                 {
     *                     "id": 1,
     *                     "transactionReference": "MERCHANT_REF_1",
     *                     "contributorEmail": "contributor1@example.com",
     *                     "amount": 100,
     *                     "created_at": "2024-08-18T00:00:00.000000Z",
     *                     "updated_at": "2024-08-18T00:00:00.000000Z"
     *                 },
     *                 ...
     *             ]
     *         },
     *         ...
     *     }
     * }
     *
     * @response 404 {
     *     "message": "No data found for the specified ajo_id"
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     * @get /get-ajo-contributors/{ajo_id}
     */

public function getAjoContributors(Request $request, $ajo_id) {
    $perPage = $request->input('per_page', 10);

    // Retrieve all rows for the specified 'ajo_id'
    $check = Invitation::where('ajo_id', $ajo_id)->get();

    if ($check->count() > 0) {
        $ajoContributorsData = [];

        // Loop through each row
        foreach ($check as $invitation) {
            // Extract 'merchantReference' for the current row
            $merchantReference = $invitation->merchantReference;

            // Retrieve AjoContributors with the correct 'merchantReference'
            $ajoContributors = AjoContributor::where('transactionReference', $merchantReference)
                ->paginate($perPage)->toArray();

            // Extract email for the current row
            $email = $invitation->email;

            // Group AjoContributors by 'transactionReference' and include email
            $ajoContributorsData[$merchantReference] = [
                'transactionReference' => $merchantReference,
                'email' => $email,
                'ajoContributors' => $ajoContributors['data'],
            ];
        }

        return response([
            'data' => $ajoContributorsData,
            'message' => 'AjoContributors retrieved successfully',
        ], 200);
    } else {
        return response([
            'message' => 'No data found for the specified ajo_id',
        ], 404);
    }
}

    /**
     * @group Ajo
     *
     * Create a new Ajo.
     *
     * This endpoint allows authenticated users to create a new Ajo. It requires various details about the Ajo, including its name, account number, description, frequency, member count, starting date, cycle, and the amount per member. Upon successful creation, a unique code is generated for the Ajo. The user ID of the authenticated user is associated with the newly created Ajo.
     *
     * @bodyParam name string required The name of the Ajo. Example: "Weekly Savings Club"
     * @bodyParam account_number string required The account number associated with the Ajo. Example: "1234567890"
     * @bodyParam description string required A brief description of the Ajo. Example: "Ajo for weekly savings with community participation."
     * @bodyParam frequency string required How often the Ajo cycle occurs. Example: "weekly"
     * @bodyParam member_count int required The total number of members in the Ajo. Example: 20
     * @bodyParam starting_date string required The start date of the Ajo. Format: YYYY-MM-DD. Example: "2024-08-01"
     * @bodyParam cycle string required The duration of one complete cycle. Example: "4 weeks"
     * @bodyParam amount_per_member float required The amount each member contributes per cycle. Example: 5000.00
     *
     * @response 201 {
     *     "id": 1,
     *     "name": "Weekly Savings Club",
     *     "account_number": "1234567890",
     *     "description": "Ajo for weekly savings with community participation.",
     *     "unique_code": "A1B2C3D4E5",
     *     "frequency": "weekly",
     *     "member_count": 20,
     *     "starting_date": "2024-08-01",
     *     "cycle": "4 weeks",
     *     "amount_per_member": 5000.00,
     *     "user_id": 1,
     *     "created_at": "2024-08-18T00:00:00.000000Z",
     *     "updated_at": "2024-08-18T00:00:00.000000Z"
     * }
     *
     * @response 422 {
     *     "message": "Validation Error",
     *     "errors": {
     *         "name": ["The name field is required."],
     *         "account_number": ["The account number field is required."],
     *         "description": ["The description field is required."],
     *         "frequency": ["The frequency field is required."],
     *         "member_count": ["The member count field is required."],
     *         "starting_date": ["The starting date field is required."],
     *         "cycle": ["The cycle field is required."],
     *         "amount_per_member": ["The amount per member field is required."]
     *     }
     * }
     *
     * @response 500 {
     *     "error": "Unsuccessful"
     * }
     */


public function createAjo(Request $request)
    {

        $ajo = Ajo::create([

            'name' => $request->input('name'),
            'account_number' => $request->input('account_number'),
            'description' => $request->input('description'),
            'unique_code' => Str::random(10),
            'frequency' => $request->input('frequency'),
            'member_count' => $request->input('member_count'),
            'starting_date' => $request->input('starting_date'),
            'cycle' => $request->input('cycle'),
            'amount_per_member' => $request->input('amount_per_member'),
            'user_id' => auth()->user()->id,
        ]);

        return response()->json($ajo);
    }





    private function calculateNextPaymentDates($startingDate, $frequency, $permittedMember, $cycleMultiplier)
    {
        $nextPaymentDates = [];
        $paymentDate = $startingDate;

        for ($i = 0; $i < $permittedMember; $i++) {
            $nextPaymentDates[] = $paymentDate;

            // Update paymentDate based on the request frequency
            switch ($frequency) {
                case 'Daily':
                    $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier days", strtotime($paymentDate)));
                    break;
                case 'Weekly':
                    $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier weeks", strtotime($paymentDate)));
                    break;
                case 'Monthly':
                    $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier months", strtotime($paymentDate)));
                    break;
                case 'Quarterly':
                    $paymentDate = date('Y-m-d', strtotime("+$cycleMultiplier months", strtotime($paymentDate)));
                    break;
            }
        }

        return $nextPaymentDates;
    }



    private function calculateCollectionDate($startingDate, $frequency, $position)
    {
        switch ($frequency) {
            case 'Daily':
                return date('Y-m-d', strtotime("-1 day", strtotime("+$position days", strtotime($startingDate))));
            case 'Weekly':
                return date('Y-m-d', strtotime("-7 days", strtotime("+$position weeks", strtotime($startingDate))));
            case 'Monthly':
                return date('Y-m-d', strtotime("-1 month", strtotime("+$position months", strtotime($startingDate))));
            case 'Quarterly':
                return date('Y-m-d', strtotime("-3 months", strtotime("+$position months", strtotime($startingDate))));
            default:
                return null;
        }
    }



    private function addProduct(array $requestData): array
    {
        try {
            // Validate the incoming request data
            $validatedData = validator($requestData, [
                'productName' => 'required|string',
                'productDescription' => 'required|string',
            ])->validate();

            // Return validated data as array
            return [
                'productName' => $validatedData['productName'],
                'productDescription' => $validatedData['productDescription'],
            ];
        } catch (\Exception $e) {
            Log::error('Error validating product data: ' . $e->getMessage());
            throw new \Exception('Failed to add product. Please try again later.');
        }
    }


    /**
     * @group Ajo
     *
     * Invite users to an Ajo.
     *
     * This endpoint allows authenticated users to invite other users to join a specific Ajo group. It performs the following actions:
     * 1. Validates if the number of invitations does not exceed the allowed member count for the Ajo.
     * 2. Creates invitations for the provided users.
     * 3. Calculates the payment schedules for the invited users based on the Ajo's frequency.
     * 4. Interacts with an external API to create a product and get a product ID.
     * 5. Generates invitation links for the users and sends them via email if they are not the authenticated user.
     *
     * @bodyParam users array required A list of users to invite.
     * @bodyParam users.email string required The email address of the user to invite. Example: "johndoe@example.com"
     * @bodyParam users.name string optional The name of the user. Example: "John Doe"
     * @bodyParam users.phone_number string optional The phone number of the user. Example: "+1234567890"
     * @bodyParam users.position string optional The position of the user within the Ajo group. Example: "Member"
     *
     * @response 200 {
     *     "status": 200,
     *     "message": "Invitations sent successfully",
     *     "links": [
     *         "https://www.azatme.com/login?invitee_name=JohnDoe&email=johndoe@example.com&inviter_token=abc123&position=Member&productId=12345&ajoId=1&frequency=Monthly&productName=ProductName"
     *     ]
     * }
     *
     * @response 400 {
     *     "message": "Members cannot be more than X"
     * }
     *
     * @response 400 {
     *     "message": "Invalid user data"
     * }
     *
     * @response 500 {
     *     "message": "Error in response from API"
     * }
     *
     * @response 500 {
     *     "message": "API request failed"
     * }
     *
     * @response 500 {
     *     "message": "Error creating DirectDebitProduct"
     * }
     */

    /**
     * @group Product
     *
     * Add a new product.
     *
     * This method validates the provided product data and returns it if valid. It ensures that the product name and description are provided and correctly formatted. In case of validation failure, it logs the error and throws an exception.
     *
     * @bodyParam productName string required The name of the product. Example: "Monthly Subscription"
     * @bodyParam productDescription string required A description of the product. Example: "A subscription for monthly services."
     *
     * @response 200 {
     *     "productName": "Monthly Subscription",
     *     "productDescription": "A subscription for monthly services."
     * }
     *
     * @response 422 {
     *     "message": "Validation Error",
     *     "errors": {
     *         "productName": ["The product name field is required."],
     *         "productDescription": ["The product description field is required."]
     *     }
     * }
     *
     * @response 500 {
     *     "message": "Failed to add product. Please try again later."
     * }
     * @post /invitation/{ajoId}
     */


    public function inviteUserToAjo(Request $request, $ajoId)
    {
        $ajo = Ajo::findOrFail($ajoId);
        $permittedMember = $ajo->member_count;
        $startingDate = $ajo->starting_date;
        $frequency = $ajo->frequency;
        $limiter = Invitation::where('inviter_id', auth()->user()->id)->where('ajo_id', $ajo->id)->count();
        $payload = $request->all();

        if ($limiter > $permittedMember) {
            return response()->json(['message' => 'Members cannot be more than ' . $permittedMember], 400);
        }

        if (empty($payload['users'])) {
            return response()->json(['message' => 'Invalid user data'], 400);
        }

        $users = $payload['users'];
        $inviteLinks = [];
        $cycleMultiplier = 1;
        $authUserEmail = auth()->user()->email;

        // Generate the product only once
        try {
            $productData = $this->addProduct($payload);

            $product = new DirectDebitProduct();
            $product->productName = $productData['productName'];
            $product->productDescription = $productData['productDescription'];
            $product->isUserResponsibleForCharges = true;
            $product->classification = "FixedContract";
            $product->partialCollectionEnabled = false;
            $product->user_id = Auth::user()->id;
            $product->save();

            // Prepare the payload for the API
            $productPayload = [
                'productName' => $product->productName,
                'productDescription' => $product->productDescription,
                'isUserResponsibleForCharges' => $product->isUserResponsibleForCharges,
                'classification' => $product->classification,
                'partialCollectionEnabled' => $product->partialCollectionEnabled,
            ];

            Log::info('Payload sent to gateway: ' . json_encode($productPayload));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ApplicationId' => $this->apiKey,
            ])->post($this->apiUrl . '/Product/create', $productPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['succeed']) {
                    $productId = $responseData['data']['productId'];
                    $product->productId = (string)$productId;
                    $product->save();
                    Log::info('Response from paythru API: ' . $response->body());
                } else {
                    Log::error('Error in response from API: ' . $response->body());
                    return response()->json(['message' => 'Error in response from API'], 500);
                }
            } else {
                Log::error('API request failed: ' . $response->body());
                return response()->json(['message' => 'API request failed'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error creating DirectDebitProduct: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating DirectDebitProduct'], 500);
        }

        // Process each user with the single productId
        foreach ($users as $user) {
            if (!isset($user['email'])) {
                continue;
            }

            $existingUser = User::where('email', $user['email'])->first();

            // Create a new invitation for the user
            $invitation = new Invitation();
            $invitation->email = $user['email'];
            $invitation->inviter_id = auth()->id();
            $invitation->ajo_id = $ajo->id;
            $invitation->amount = $ajo->amount_per_member;
            $invitation->token = Str::random(6);
            $invitation->position = $user['position'] ?? ($existingUser->position ?? '');
            $invitation->name = $existingUser->name ?? ($user['name'] ?? '');
            $invitation->phone_number = $existingUser->phone_number ?? ($user['phone_number'] ?? '');
            $invitation->save();

            // Calculate the next payment dates based on starting date and frequency for this user
            $nextPaymentDates = $this->calculateNextPaymentDates($startingDate, $frequency, $permittedMember, $cycleMultiplier);

            // Insert payment dates into the payment_dates table for this user
            foreach ($nextPaymentDates as $paymentDate) {
                $paymentData = new PaymentDate();
                $paymentData->invitation_id = $invitation->id;
                $paymentData->payment_date = $paymentDate;

                $position = $user['position'] ?? null;
                $collectionDate = $this->calculateCollectionDate($startingDate, $frequency, $position);
                $paymentData->position = $invitation->position;
                $paymentData->collection_date = $collectionDate;
                $paymentData->save();
            }

            // Generate invite link using the single productId
            $inviteLink = $existingUser
                ? 'https://www.azatme.com/login?invitee_name=' . $existingUser->name . '&email=' . $existingUser->email . '&inviter_token=' . $invitation->token . '&position=' . $invitation->position . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName
                : 'https://www.azatme.com/register?invitee_name=' . $invitation->name . '&email=' . $invitation->email . '&phone_number=' . $invitation->phone_number . '&position=' . $invitation->position . '&inviter_token=' . $invitation->token . '&productId=' . $product->productId . '&ajoId=' . $ajoId . '&frequency=' . $ajo->frequency . '&productName=' . $product->productName;

            $inviteLinks[] = $inviteLink;

            // Send email if the invited user is not the authenticated user
            if ($user['email'] !== $authUserEmail) {
                Mail::to($user['email'])->send(new MyEmail(
                    $invitation->name,
                    $nextPaymentDates,
                    $collectionDate,
                    $inviteLink
                ));
            }
        }

        return response()->json(['status' => 200, 'message' => 'Invitations sent successfully', 'links' => $inviteLinks]);
    }


    /**
     * Accept an Ajo invitation.
     *
     * @bodyParam ajoId integer required The ID of the Ajo invitation to accept. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Invitation accepted successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Invitation not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /accept-ajo-invite
     */

public function acceptInvitation(Request $request)
{

   // $this->paymentLinkService->sendPaymentLinkToUsers();

    $inviteLink = $request->input('inviteLink');

    if (strpos($inviteLink, 'action=accept') !== false) {
        $query = parse_url($inviteLink, PHP_URL_QUERY);
        parse_str($query, $params);
        $inviterToken = $params['inviter_token'];

        $invitation = Invitation::where('token', $inviterToken)->first();

        if ($invitation) {
            // Update the status column to 'accept'
            $invitation->status = 'accept';
            $invitation->save();

            $accountNumber = $request->input('account_number');
            $bankName = $request->input('bank');
            $accountName = $request->input('account_name');
            $bankCode = $request->input('bankCode');

            // Check if the account number already exists in the Bank table
            $bank = Bank::where('account_number', $accountNumber)->first();

            if (!$bank) {
                // If the account number doesn't exist, create it in the Bank table
                $bank = Bank::create([
                    'account_number' => $accountNumber,
                    'bank_name' => $bankName,
                    'account_name' => $accountName,
                    'bankCode' => $bankCode,
                ]);
            }

            return response()->json(['message' => 'Invitation accepted successfully']);
        } else {
            return response()->json(['error' => 'Invalid invitation token'], 400);
        }
    } else {
        return response()->json(['error' => 'Invalid action'], 400);
    }
}


    /**
     * Retrieve Ajo details by ID.
     *
     * @urlParam ajoId integer required The ID of the Ajo. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "ajo_id": 1,
     *     "name": "Monthly Ajo",
     *     "amount": 5000,
     *     "description": "Monthly savings plan"
     *   },
     *   "message": "Ajo details retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Ajo not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-ajo-by-id/{ajoId}
     */



public function getAjoById(Request $request, $id)
{
    $perPage = $request->input('per_page', 10);

    // Validate the $id parameter to ensure it's a positive integer.
    if ($id){

    $getAjoById = Invitation::where('inviter_id', Auth::id())
        ->where('ajo_id', $id)
        ->latest()
        ->paginate($perPage);
    return response()->json($getAjoById);
} else {
            return response()->json(['error' => 'No Id found'], 400);
        }
}


    /**
     * Decline an Ajo invitation.
     *
     * @bodyParam ajoId integer required The ID of the Ajo invitation to decline. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Invitation declined successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Invitation not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /decline-ajo
     */

public function declineInvitation(Request $request)
{

    $inviteLink = $request->input('inviteLink');


    if (strpos($inviteLink, 'action=decline') !== false) {
        // Extract the token from the inviteLink
        $query = parse_url($inviteLink, PHP_URL_QUERY);
        parse_str($query, $params);
        $inviterToken = $params['inviter_token'];

      $invitation = Invitation::where('token', $inviterToken)->first();

        if ($invitation) {
            // Update the status column to decline
            $invitation->status = 'decline';
            $invitation->save();

            // Create a Decline record if needed
            $ajo = Decline::create([
                'remark' => $request->input('remark'),
                'reason' => $request->input('reason'),
                'invitation_id' => $invitation->id,
                'user_id' => $invitation->inviter_id,
                'invitee_name' => $invitation->name,
            ]);
		return response()->json(['message' => 'Invitation declined successfully','Data' => $ajo]);
        } else {
            return response()->json(['error' => 'Invalid invitation token'], 400);
        }
    } else {
        return response()->json(['error' => 'Invalid action'], 400);
    }
}

    /**
     * @group Ajo
     *
     * Get all Ajo created by the authenticated user.
     *
     * This endpoint retrieves all Ajo records created by the currently authenticated user. It supports pagination through the `per_page` query parameter, allowing clients to specify the number of records per page.
     *
     * @queryParam per_page int The number of items to return per page. Default is 10. Example: 15
     *
     * @response 200 {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "name": "Weekly Savings Club",
     *             "account_number": "1234567890",
     *             "description": "Ajo for weekly savings with community participation.",
     *             "unique_code": "A1B2C3D4E5",
     *             "frequency": "weekly",
     *             "member_count": 20,
     *             "starting_date": "2024-08-01",
     *             "cycle": "4 weeks",
     *             "amount_per_member": 5000.00,
     *             "user_id": 1,
     *             "created_at": "2024-08-18T00:00:00.000000Z",
     *             "updated_at": "2024-08-18T00:00:00.000000Z"
     *         }
     *     ],
     *     "first_page_url": "http://example.com/api/ajo?per_page=10&page=1",
     *     "last_page": 1,
     *     "last_page_url": "http://example.com/api/ajo?per_page=10&page=1",
     *     "next_page_url": null,
     *     "path": "http://example.com/api/ajo",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     * }
     * @get /get-ajo-per-user
     */


public function getAllAjoCreatedPerUser(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $AuthUser = Auth::user()->id;
    $getAllAjoCreatedPerUser = Ajo::where('user_id', $AuthUser)->latest()->paginate($perPage);
    return response()->json($getAllAjoCreatedPerUser);
}

    /**
     * @group Ajo
     *
     * Get all Ajo invitations created by the authenticated user and invitations they have been added to.
     *
     * This endpoint retrieves:
     * 1. A list of Ajo records where the authenticated user is the inviter, including details and total paid amounts.
     * 2. A list of invitations where the authenticated user has been invited, including the details of the Ajo and invitation.
     *
     * Both lists are paginated through the `per_page` query parameter.
     *
     * @queryParam per_page int The number of items to return per page. Default is 10. Example: 15
     *
     * @response 200 {
     *     "getAuthUserInvitationCreated": {
     *         "current_page": 1,
     *         "data": [
     *             {
     *                 "id": 1,
     *                 "name": "Weekly Savings Club",
     *                 "description": "Ajo for weekly savings with community participation.",
     *                 "starting_date": "2024-08-01",
     *                 "frequency": "weekly",
     *                 "amount_per_member": 5000.00,
     *                 "cycle": "4 weeks",
     *                 "member_count": 20,
     *                 "total_paid": 50000.00
     *             }
     *         ],
     *         "first_page_url": "http://example.com/api/ajo/invitations/created?per_page=10&page=1",
     *         "last_page": 1,
     *         "last_page_url": "http://example.com/api/ajo/invitations/created?per_page=10&page=1",
     *         "next_page_url": null,
     *         "path": "http://example.com/api/ajo/invitations/created",
     *         "per_page": 10,
     *         "prev_page_url": null,
     *         "to": 1,
     *         "total": 1
     *     },
     *     "getInvitationInvitedTo": {
     *         "current_page": 1,
     *         "data": [
     *             {
     *                 "id": 1,
     *                 "ajo_id": 1,
     *                 "email": "johndoe@example.com",
     *                 "name": "John Doe",
     *                 "phone_number": "+1234567890",
     *                 "position": "Member",
     *                 "inviter_id": 1,
     *                 "amount": 5000.00,
     *                 "created_at": "2024-08-18T00:00:00.000000Z",
     *                 "updated_at": "2024-08-18T00:00:00.000000Z",
     *                 "members": 20,
     *                 "startDate": "2024-08-01",
     *                 "freq": "weekly",
     *                 "cycle": "4 weeks",
     *                 "Ajo_Name": "Weekly Savings Club",
     *                 "Descrip": "Ajo for weekly savings with community participation."
     *             }
     *         ],
     *         "first_page_url": "http://example.com/api/ajo/invitations/invited-to?per_page=10&page=1",
     *         "last_page": 1,
     *         "last_page_url": "http://example.com/api/ajo/invitations/invited-to?per_page=10&page=1",
     *         "next_page_url": null,
     *         "path": "http://example.com/api/ajo/invitations/invited-to",
     *         "per_page": 10,
     *         "prev_page_url": null,
     *         "to": 1,
     *         "total": 1
     *     }
     * }
     * @get /get-invitation
     */
public function getAllAjoInvitationCreatedPerUser(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $getAuthUser = Auth::user();
   // $this->paymentLinkService->sendPaymentLinkToUsers();
    // Get the Ajo IDs where the user is the inviter
    $ajoIds = DB::table('ajos')
        ->join('invitations', function ($join) use ($getAuthUser) {
            $join->on('ajos.id', '=', 'invitations.ajo_id')
                ->where('ajos.user_id', '=', $getAuthUser->id);
        })
        ->select('ajos.id')
        ->groupBy('ajos.id')
        ->pluck('ajos.id');

    // Get user invitations with total paid amounts
    $getUserInvitation = DB::table('ajos')
        ->join('invitations', function ($join) {
            $join->on('ajos.id', '=', 'invitations.ajo_id');
        })
        ->select('ajos.id', 'ajos.name', 'ajos.description', 'ajos.starting_date', 'ajos.frequency', 'ajos.amount_per_member', 'ajos.cycle', 'ajos.member_count', DB::raw('SUM(invitations.residualAmount) as total_paid'))
        ->whereIn('ajos.id', $ajoIds)
        ->groupBy('ajos.id', 'ajos.name', 'ajos.description', 'ajos.starting_date', 'ajos.frequency', 'ajos.amount_per_member', 'ajos.cycle', 'ajos.member_count')
	->orderBy('ajos.created_at', 'desc')
        ->paginate($perPage);

    // Get invitations added by the user
    $getUserInvitationAddedTransactions = DB::table('invitations')
        ->join('ajos', 'invitations.ajo_id', '=', 'ajos.id')
        ->where('invitations.email', $getAuthUser->email)
        ->select(
            'invitations.*',
            'ajos.member_count as members',
            'ajos.starting_date as startDate',
            'ajos.frequency as freq',
            'ajos.amount_per_member as amount',
            'ajos.cycle as cycle',
            'ajos.name as Ajo_Name',
            'ajos.description as Descrip'
        )
	->orderBy('ajos.created_at', 'desc')
        ->paginate($perPage);

    return response()->json([
        'getAuthUserInvitationCreated' => $getUserInvitation,
        'getInvitationInvitedTo' => $getUserInvitationAddedTransactions,
    ]);
}


    /**
     * Handle Ajo webhook response.
     *
     * @bodyParam webhookData array required The data received from the webhook. Example: {"status": "paid", "ajoId": 1}
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Webhook processed successfully."
     * }
     *
     * @response 400 {
     *   "status": "error",
     *   "message": "Invalid webhook data."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @post /agowebhook
     */

public function webhookAjoResponse(Request $request)
{
    try {
        $productId = env('PayThru_ajo_productid');
        $response = $request->all();
        $dataEncode = json_encode($response);
        $data = json_decode($dataEncode);
	$modelType = "Ajo";

	Log::info("Starting webhookAjoResponse", ['data' => $data, 'modelType' => $modelType]);
        if ($data->notificationType == 1) {
            if (is_null($data->transactionDetails->paymentReference)) {
         $invitation = Invitation::where('merchantReference', $data->transactionDetails->merchantReference)->first();

                $product_action = "payment";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType, $product_action);
                }


	if ($invitation) {
	$AjoContributor = new AjoContributor([
           'payThruReference' => $data->transactionDetails->payThruReference,
            'ajo_id' => $invitation->ajo_id,
            'transactionReference' => $data->transactionDetails->merchantReference,
            'fiName' => $data->transactionDetails->fiName,
            'status' => $data->transactionDetails->status,
            'amount' => $data->transactionDetails->amount,
            'responseCode' => $data->transactionDetails->resultCode ?? null,
            'paymentMethod' => $data->transactionDetails->paymentMethod,
            'commission' => $data->transactionDetails->commission,
            'residualAmount' => $data->transactionDetails->residualAmount,
            'responseDescription' => $data->transactionDetails->responseDescription ?? null,
            'providedEmail' => $data->transactionDetails->customerInfo->providedEmail,
            'providedName' => $data->transactionDetails->customerInfo->providedName,
            'remarks' => $data->transactionDetails->customerInfo->remarks ?? null,
        ]);
	$AjoContributor->save();
//	$invitation = Invitation::where('merchantReference', $data->transactionDetails->merchantReference)->first();
//	if ($invitation) {
	$invitation->residualAmount += $AjoContributor->residualAmount;
        $invitation->save();
}

        Log::info("Ajo Contributor saved in Contributor table");
        Log::info("Invitation updated");

}
        } elseif ($data->notificationType == 2) {
            if (isset($data->transactionDetails->transactionReferences[0])) {
                $transactionReferences = $data->transactionDetails->transactionReferences[0];
                Log::info("Received ajo withdrawal notification for transaction references: " . $transactionReferences);

                // Update withdrawal
                $withdrawal = AjoWithdrawal::where('transactionReference', $transactionReferences)->first();
                $product_action = "withdrawal";
                $referral = ReferralSetting::where('status', 'active')
                    ->latest('updated_at')
                    ->first();
                if ($referral) {
                    $this->referral->checkSettingEnquiry($modelType, $product_action);
                }
                if ($withdrawal) {
                    $uniqueId = $withdrawal->uniqueId;

                    $updateAjoWithdrawal = AjoWithdrawal::where([
                        'transactionReference' => $transactionReferences,
                        'uniqueId' => $uniqueId
                    ])->first();

                    if ($updateAjoWithdrawal) {
                        $updateAjoWithdrawal->paymentAmount = $data->transactionDetails->paymentAmount;
                        $updateAjoWithdrawal->recordDateTime = $data->transactionDetails->recordDateTime;
                        $updateAjoWithdrawal->status = 'success';
                        $updateAjoWithdrawal->save();

                        Log::info("Ajo withdrawal updated");
                    } else {
                        Log::info("Ajo withdrawal not found for transaction references: " . $transactionReferences);
                    }
                } else {
                    Log::info("Withdrawal not found for transaction references: " . $transactionReferences);
                }
            } else {
                Log::info("Transaction references not found in the webhook data");
            }
        }

        http_response_code(200);
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error($e->getMessage());
        return response()->json(['error' => 'An error occurred'], 500);
    }
}


    /**
     * Retrieve bank details of an Ajo user.
     *
     * @urlParam id integer required The ID of the Ajo user. Example: 1
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "bank_name": "Bank XYZ",
     *     "account_number": "1234567890",
     *     "account_name": "John Doe"
     *   },
     *   "message": "Bank details retrieved successfully."
     * }
     *
     * @response 404 {
     *   "status": "error",
     *   "message": "Ajo user not found."
     * }
     *
     * @response 500 {
     *   "status": "error",
     *   "message": "Server error. Please try again later."
     * }
     *
     * @get /get-ajo-user-bank-details/{id}
     */

public function getUsersWithBankInfo($ajo_id) {
    // Enable query logging
    DB::enableQueryLog();

    // Build and execute the query
    $invitations = DB::table('invitations')
        ->select('invitations.*', 'banks.*')
        ->join('users', 'invitations.email', '=', 'users.email')
        ->join('banks', 'users.id', '=', 'banks.user_id')
        ->where('invitations.ajo_id', $ajo_id)
        ->get();

   // $queries = DB::getQueryLog();
   return response()->json(['message' => 'successfully','Data' => $invitations]);
   // return ['invitations' => $invitations, 'queries' => $queries];
}


    /**
     * @group Ajo
     *
     * Retrieve users who have accepted the Ajo invitation but have not made a payment.
     *
     * This endpoint retrieves a list of users who have accepted an invitation to an Ajo but have not made a payment. It filters users based on their invitation status and checks if their payment date is in the future.
     *
     * @urlParam id int required The unique identifier of the Ajo to retrieve unpaid users for. Example: 1
     *
     * @response 200 [
     *     {
     *         "id": 1,
     *         "ajo_id": 1,
     *         "email": "user@example.com",
     *         "status": "accept",
     *         "residualAmount": null,
     *         "created_at": "2024-08-18T00:00:00.000000Z",
     *         "updated_at": "2024-08-18T00:00:00.000000Z"
     *     },
     *     ...
     * ]
     *
     * @response 404 {
     *     "message": "No unpaid users found for the specified Ajo."
     * }
     * @get /get-unpaid-users/{id}
     */


public function getUnpaidAjoUsers($id)
{
    $unpaidUsers = Invitation::where('ajo_id', $id)
        ->where('status', 'accept')
        ->where('residualAmount', null)
        ->get();

    $nowPlusOneDay = now()->addDay();

    $unpaidUsers = $unpaidUsers->filter(function ($invite) use ($nowPlusOneDay) {
        $paymentDate = PaymentDate::where('invitation_id', $invite->invitation_id)->first();

        return $paymentDate && $paymentDate->payment_date > $nowPlusOneDay;
    });

    return $unpaidUsers;
}

    /**
     * @group Ajo
     *
     * Process a payout request for an Ajo account.
     *
     * This endpoint allows a user to request a payout from their Ajo account. It performs various checks, applies charges, and interacts with an external service to handle the payout request.
     *
     * @bodyParam amount float required The amount to withdraw. Example: 1500.00
     * @bodyParam account_number string required The user's bank account number. Example: '1234567890'
     * @bodyParam bank string required The name of the bank. Example: 'Example Bank'
     * @bodyParam description string optional A description for the withdrawal. Example: 'Monthly payout'
     *
     * @response 200 {
     *     "transactionReference": "abcdef123456",
     *     "status": "success"
     * }
     *
     * @response 400 {
     *     "message": "You cannot withdraw an amount less than 100 after commission"
     * }
     * @response 404 {
     *     "message": "Bank account not found"
     * }
     * @response 403 {
     *     "error": "Access denied. You do not have permission to access this resource."
     * }
     * @response 500 {
     *     "message": "Payout request failed"
     * }
     * @post /ajo-payout
     */

public function AjoPayout(Request $request)
    {
        $current_timestamp = now();
        $timestamp = strtotime($current_timestamp);
        $secret = env('PayThru_App_Secret');
        $productId = env('PayThru_ajo_productid');
        $hash = hash('sha512', $timestamp . $secret);
        $AppId = env('PayThru_ApplicationId');
        $prodUrl = env('PayThru_Base_Live_Url');
        $charges = env('PayThru_Withdrawal_Charges');

        $requestAmount = $request->amount;


        $latestCharge = Charge::orderBy('updated_at', 'desc')->first();
        $applyCharges = $this->chargeService->applyCharges($latestCharge);


        $requestAmount = $request->amount;
	$AjoBalance = AjoWithdrawal::where('beneficiary_id', Auth::user()->id)->whereNotNull('status')->sum('amount');
   	$getAjoTransactions = Invitation::where('email', Auth::user()->email)->sum('residualAmount');
    	$AjoTransactions = $getAjoTransactions - $AjoBalance;

	if ($requestAmount < 100) {
            return response()->json(['message' => 'You cannot withdraw an amount less than 100 after commission'], 400);
        }

        if ($AjoTransactions) {
            if ($requestAmount > $AjoTransactions) {
                return response()->json(['message' => 'You do not have sufficient amount in your RefundMe A'], 400);
            }
        $minusResidual = $AjoTransactions - $requestAmount;
	}
        $refundmeAmountWithdrawn = $requestAmount - $latestCharge->charges;
        $acct = $request->account_number;

        $bank = Bank::where('account_number', $acct)
            ->first();

        if (!$bank) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        $beneficiaryReferenceId = $bank->referenceId;
	    $benefit = $bank->user_id;
        $token = $this->paythruService->handle();

        if (!$token) {
            return "Token retrieval failed";
        } elseif (is_string($token) && strpos($token, '403') !== false) {
            return response()->json([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ], 403);
        }

        $data = [
            'productId' => $productId,
            'amount' => $refundmeAmountWithdrawn,
        'beneficiary' => [
                'nameEnquiryReference' => $beneficiaryReferenceId
            ],
        ];

        $url = $prodUrl . '/transaction/settlement';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            return response()->json(['message' => 'Payout request failed'], 500);
        }


       // Invitation::where('email', auth()->user()->email)->where('stat', 1)
         //   ->latest()->update(['minus_residual' => $minusResidual]);

	 AjoBalanace::create([
        	'user_id' => Auth::user()->id,
        	'balance' => $minusResidual,
		'action' => 'debit',
    	]);


        // Save the withdrawal details
            $withdrawal = new AjoWithdrawal([
            'accountNumber' => $request->accountNumber,
            'description' => $request->description,
            'beneficiary_id' => $benefit,
            'amount' => $requestAmount - $charges,
            'bank' => $request->bank,
            'charges' => $charges,
            'uniqueId' => Str::random(10),
        ]);
	 AjoBalanace::create([
        	'user_id' => Auth::user()->id,
        	'balance' => $minusResidual,
		    'action' => 'debit',
    	]);

        if ($applyCharges) {
            // Save the withdrawal details with charges
            $withdrawal = new AjoWithdrawal([
                'account_number' => $request->account_number,
                'description' => $request->description,
                'beneficiary_id' => auth()->user()->id,
                'amount' => $requestAmount - $latestCharge->charges,
                'bank' => $request->bank,
                'charges' => $latestCharge->charges,
                'uniqueId' => Str::random(10),
            ]);
        } else {
            // Save the withdrawal details without charges
            $withdrawal = new AjoWithdrawal([
                'account_number' => $request->account_number,
                'description' => $request->description,
                'beneficiary_id' => auth()->user()->id,
                'amount' => $requestAmount,
                'bank' => $request->bank,
                'uniqueId' => Str::random(10),
            ]);
        }

        $withdrawal->save();

        $collection = $response->json();

        Log::info('API response: ' . json_encode($collection));
        $saveTransactionReference = AjoWithdrawal::where('uniqueId', $withdrawal->uniqueId)
            ->update([
                'transactionReference' => $collection['transactionReference'],
                'status' => $collection['message'],

            ]);

        return response()->json($saveTransactionReference, 200);
    }

    /**
     * @group Ajo
     *
     * Retrieve the list of withdrawal transactions for the authenticated user.
     *
     * This endpoint retrieves all withdrawal transactions where the authenticated user is the beneficiary. The transactions are ordered by creation date in descending order.
     *
     * @response 200 {
     *     "id": 1,
     *     "account_number": "1234567890",
     *     "description": "Monthly payout",
     *     "beneficiary_id": 1,
     *     "amount": 1500.00,
     *     "bank": "Example Bank",
     *     "charges": 50.00,
     *     "uniqueId": "abcdef123456",
     *     "transactionReference": "tx123456789",
     *     "status": "success",
     *     "created_at": "2024-08-18T12:34:56Z",
     *     "updated_at": "2024-08-18T12:34:56Z"
     * }
     *
     * @response 404 {
     *     "message": "Transaction not found for this user"
     * }
     * @get /get-ajo-withdrawal
     */

public function getAjoWithdrawalTransaction(Request $request)
{
//    $perPage = $request->query('per_page', 10);

  //  $page = $request->query('page', 1);

    // Retrieve paginated transactions
    $getWithdrawalTransaction = AjoWithdrawal::where('beneficiary_id', auth()->user()->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Check if there are any items
    if ($getWithdrawalTransaction->isNotEmpty()) {
        return response()->json($getWithdrawalTransaction);
    } else {
        return response()->json([
            'message' => 'Transaction not found for this user'
        ], 404);
    }
}



public function sendPaymentLinkToUsers()
{
    $productId = env('PayThru_ajo_productid');
    $secret = env('PayThru_App_Secret');
    $owners = PaymentDate::whereDate('collection_date', now()->toDateString())->get();

    foreach ($owners as $owner) {
        $ajoBenefit = Invitation::where('id', $owner->invitation_id)->first();
        $ajoIds = $ajoBenefit->ajo_id;
        $ben = $ajoBenefit->name;
        $today = Carbon::today();
        $users = PaymentDate::whereDate('payment_date', $today->toDateString())
            ->with('invitation')
            ->whereHas('invitation', function ($query) use ($ajoIds) {
                $query->where('ajo_id', $ajoIds);
            })
            ->get();

        // Generate the payment link once for the AJO and collection date
        $token = $this->paythruService->handle();
       	$amount = $users->first()->invitation->amount;

        $ajoId = $users->first()->invitation->ajo_id;
        $ajoN = Ajo::where('id', $ajoId)->first();
        $ajoName = $ajoN->name;
        $paymentLinks = $this->generatePaymentLink($amount, $ajoId, $token, $secret, $productId);

        if (!$paymentLinks) {
            return response()->json([
                'error' => 'Failed to generate payment link for AJO ID ' . $ajoId
            ], 500);
        }

        $payLink = $paymentLinks['paymentLink'];

        foreach ($users as $user) {
            $email = $user->invitation->email;
            $status = $user->invitation->status;
            $day = $today->toDateString();

            $emailAlreadySent = AjopaymentSent::where([
                'email' => $email,
                'ajo_id' => $ajoId,
                'status' => 1,
                'date_sent' => now()->toDateString(),
            ])->first();

            if (!$emailAlreadySent && ($status === null || $status === 'decline')) {
                // Log that the user has not accepted the invitation
                Log::info('Payment link not sent to ' . $email . ' as the user has not accepted the AJO invitation.');
            }
            elseif (!$emailAlreadySent) {
                $getLastString = (explode('/', $payLink));
                $now = end($getLastString);

                Invitation::where([
                    'email' => $ajoBenefit->email,
                    'ajo_id' => $ajoId,
                    'token' => $ajoBenefit->token,
                ])->update([
                    'paymentReference' => $now,
                    'merchantReference' => $paymentLinks['transId'],
                ]);

                $auth = Auth::user()->name;
                $this->sendPaymentLinkByEmail($email, $payLink, $ajoName, $auth, $day, $ben);
                // Insert the record into AjopaymentSent table with status 1
                $this->markPaymentLinkAsSent($email, $ajoId);
                Log::info('Payment link sent to ' . $email);
            } else {
                Log::info('Email already sent for AJO ID ' . $ajoId . ' and email ' . $email);
            }
        }
    }
}


    private function generatePaymentLink($amount, $ajoId, $token, $secret, $productId)
    {
        $hashSign = hash('sha512', $amount . $secret);
        $prodUrl = env('PayThru_Base_Live_Url');

        if (!$token) {
            return null;
        }

        $data = [
            'amount' => $amount,
            'productId' => $productId,
            'transactionReference' => time() . $ajoId,
            'paymentDescription' => $token,
            'paymentType' => 2,
            'sign' => $hashSign,
            'displaySummary' => true,
        ];

        $url = $prodUrl . '/transaction/create';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $token,
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Failed to generate payment link');
            return null;
        }

    $transaction = json_decode($response->body(), true);
    $transId = $data['transactionReference'];
    $paymentLink = $transaction['payLink'];

    return compact('transId', 'paymentLink');
    }

    private function sendPaymentLinkByEmail($email, $payLink, $ajoName, $auth, $day, $ben)
    {
        if ($payLink) {
            Mail::to($email)->send(new PaymentLinkMail($payLink, $ajoName, $auth, $day, $ben));
        }
    }

    private function markPaymentLinkAsSent($email, $ajoId)
    {
        AjopaymentSent::insert([
            'email' => $email,
            'status' => 1,
            'date_sent' => now()->toDateString(),
            'ajo_id' => $ajoId,
        ]);
    }




}
