<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserKYCRequest;
use App\Kyc;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\User;
use App\Merchant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Sponsor;
use App\Business;




class KycController extends Controller
{


  /**
     * Get authenticated user's KYC data along with their merchants.
     */
    public function getKycWithMerchants()
    {
        try {
            $user = Auth::user();

            // Fetch KYC data for the authenticated user
            $kycData = Kyc::where('user_id', $user->id)->first();

            if (!$kycData) {
                return response()->json([
                    'message' => 'No KYC data found for this Merchant.',
                ], 404);
            }

            // Fetch all merchant data associated with the user
            $merchantData = Merchant::where('user_id', $user->id)->get();

            return response()->json([
                'message' => 'KYC data with merchant information retrieved successfully.',
                'kyc_data' => $kycData,
                'merchants' => $merchantData,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json([
                'message' => 'An error occurred while retrieving data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


public function saveKyc(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'tax_id_number' => 'required|string|max:50|unique:merchants,tax_id_number',
        'business_registration_number' => 'required|string|max:50|unique:merchants,business_registration_number',
        'business_name' => 'required|string|max:255',
    ]);

    try {
        // Create a new KYC entry
        $kyc = new Merchant();
        $kyc->tax_id_number = $validatedData['tax_id_number'];
        $kyc->user_id = Auth::user()->id;
        $kyc->business_registration_number = $validatedData['business_registration_number'];
        $kyc->business_name = $validatedData['business_name'];

        // Generate a random UUID and save it to the uuid_code field
        $kyc->uuid_code = Str::uuid()->toString();

        // Save the KYC data
        $kyc->save();

        return response()->json([
            'message' => 'KYC information saved successfully.',
            'data' => $kyc,
        ], 201);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'message' => 'An error occurred while saving KYC information.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Update KYC Profile.
 *
 * This endpoint allows Azatme merchant/sponsor user types to update their KYC (Know Your Customer) profile.
 * The merchant/sponsor must provide their business registration number for automatic verification
 * or upload files for manual verification. All parameters are required to ensure a complete KYC profile update.
 *
 * @group KYC Management
 *
 * @authenticated
 * @method POST
 *
 * @endpoint /kyc/update
 *
 * @bodyParam business_registration_number string required The business registration number for automatic verification. Example: RC123456
 * @bodyParam automatic_verification boolean required Indicates whether automatic verification using the CAC API is enabled. Example: true
 * @bodyParam identity_card file required A valid identity card document for verification.
 * @bodyParam utility_bill file required A recent utility bill for address verification.
 * @bodyParam business_registration_certificate file required A valid business registration certificate.
 * @bodyParam proof_of_address file required Proof of address document.
 * 
 * @response 200 {
 *   "success": true,
 *   "message": "KYC profile updated successfully.",
 *   "data": {
 *     "user": {
 *       "id": 1,
 *       "name": "John Doe",
 *       "email": "john.doe@example.com",
 *       "usertype": "user",
 *       "company_name": "Business XYZ",
 *       "phone": "1234567890",
 *       "password": "hashed_password",
 *       "unique_code": "ABC123",
 *       "created_at": "2024-08-18T00:00:00.000000Z",
 *       "updated_at": "2024-08-18T00:00:00.000000Z",
 *       "first_name": "John",
 *       "last_name": "Doe",
 *       "middle_name": "M",
 *       "address": "123 Main St",
 *       "nimc": "12345678901",
 *       "bvn": "12345678901",
 *       "country": "Country Name",
 *       "state": "State Name",
 *       "age": "30",
 *       "gender": "Male",
 *       "dob": "1994-01-01",
 *       "lga_of_origin": "Local Government Area",
 *       "maiden": "Maiden Name",
 *       "image": "storage/profiles/user_image.jpg"
 *     },
 *     "kyc": {
 *       "id": 1,
 *       "user_id": 1,
 *       "identity_card": "https://eduland.azatme.com/identity_cards/file.jpg",
 *       "utility_bill": "https://eduland.azatme.com/utility_bills/file.jpg",
 *       "business_registration_certificate": "https://eduland.azatme.com/business_registration_certificate/file.jpg",
 *       "proof_of_address": "https://eduland.azatme.com/proof_of_address/file.jpg",
 *       "company_name": "ABC Ltd",
 *       "share_capital": "500000",
 *       "created_at": "2024-12-01T00:00:00.000000Z",
 *       "updated_at": "2024-12-01T00:00:00.000000Z"
 *     }
 *   }
 * }
 *
 * @response 422 {
 *   "success": false,
 *   "message": "Automatic verification failed: Invalid registration number."
 * }
 *
 * @response 500 {
 *   "success": false,
 *   "message": "An error occurred while updating KYC: Server error."
 * }
 */

public function updateKYC(UpdateUserKYCRequest $request): JsonResponse
{
    $data = $request->validated();
    $userId = Auth::id();

    try {
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }
	 $user = User::findOrFail($userId);
    if ($user->usertype === 'user') {
        return response()->json([
            'success' => false,
            'message' => 'This feature is only available to merchants and sponsors, not users.',
        ], 403);
    }

 // Check if KYC data already exists for the user type
        $existingKYC = Kyc::where('user_id', $userId)->first();
        if ($existingKYC) {
            return response()->json([
                'success' => false,
                'message' => 'KYC data already exists for this usertype.',
            ], 409);
        }
		
        // Automatic verification using CAC API
        if (isset($data['business_registration_number']) && $request->input('automatic_verification', false)) {
            $response = $this->verifyCompanyWithCAC($data['business_registration_number']);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Automatic verification failed: ' . $response['message'],
                ], 422);
            }

            // Append CAC data to KYC data
            $data['company_name'] = $response['data']['company_name'] ?? null;
            $data['share_capital'] = $response['data']['share_capital'] ?? null;
        }

        // Handle file uploads and get updated data with URLs
        $uploadedData = $this->processKYCFiles($request, $data);
	 // Generate a random UUID for the uuid_code column
        $uploadedData['uuid_code'] = Str::uuid()->toString();

        // Update or create the KYC profile
        $kycProfile = Kyc::updateOrCreate(['user_id' => $userId], $uploadedData);
        return response()->json([
            'success' => true,
            'message' => 'KYC profile updated successfully.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while updating KYC: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Handle file uploads for KYC and append full URL paths, then save to the database.
 */
// private function processKYCFiles(UpdateUserKYCRequest $request, array $data): array
// {
//     $baseUrl = config('app.url') . '/storage/';
//     $fileFields = [
//         'identity_card' => 'identity_cards',
//         'utility_bill' => 'utility_bills',
//         'business_registration_certificate' => 'business_certificates',
//         'proof_of_address' => 'proof_of_address',
//     ];

//     foreach ($fileFields as $field => $folder) {
//         if ($request->hasFile($field) && $request->file($field)->isValid()) {
//             $file = $request->file($field);
//             $path = $file->store("kyc/$folder", 'public');
//             $data[$field] = $baseUrl . $path;
//         }
//     }

//     return $data;
// }

private function processKYCFiles(UpdateUserKYCRequest $request, array $data): array
{
    $fileFields = [
        'identity_card' => 'identity_cards',
        'utility_bill' => 'utility_bills',
        'business_registration_certificate' => 'business_certificates',
        'proof_of_address' => 'proof_of_address',
    ];

    foreach ($fileFields as $field => $folder) {
        if ($request->hasFile($field) && $request->file($field)->isValid()) {
            $file = $request->file($field);
            $path = $file->store("kyc/$folder", 'public'); // e.g. kyc/identity_cards/abc.jpg

            // Manually construct the URL like: Azat_pay/storage/app/public/kyc/identity_cards/abc.jpg
            $data[$field] = url('Azat_pay/storage/app/public/' . $path);
        }
    }

    return $data;
}


/**
 * Verify company with CAC API.
 */
private function verifyCompanyWithCAC(string $rcNumber): array
{
    $url = 'https://vasapp.cac.gov.ng/api/vas/engine/company/share-capital';
    $response = Http::post($url, ['rc_number' => $rcNumber]);

    if ($response->successful()) {
        $responseData = $response->json();
        if (isset($responseData['success']) && $responseData['success']) {
            return [
                'success' => true,
                'data' => $responseData['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => $responseData['message'] ?? 'Unknown error occurred',
        ];
    }

    return [
        'success' => false,
        'message' => 'Failed to connect to the CAC API',
    ];
}


public function updateStatuses(Request $request, int $userId): JsonResponse
{
    // Validate the input data
    $validated = $request->validate([
        'status' => 'required|in:pending,decline,approved',
        'description' => 'nullable|string',
    ]);

    // Retrieve the KYC record for the given user ID
    $kyc = Kyc::where('user_id', $userId)->firstOrFail();

    // Update the KYC status and status description
    $kyc->status = $validated['status'];
    $kyc->description = $validated['description'] ?? null;
    $kyc->save();

    // Send email to the user
    $user = $kyc->user; // Assuming there's a relationship between KYC and User
    $this->sendKycStatusEmail($user->email, $kyc);

    // Return the updated KYC object
    return response()->json([
        'success' => true,
        'message' => 'KYC status updated successfully.',
        'data' => $kyc,
    ], 200);
}

public function updateStatus(Request $request, int $userId): JsonResponse
{
    // Validate the input data
    $validated = $request->validate([
        'status' => 'required|in:pending,decline,approved',
        'description' => 'nullable|string',
    ]);

    // Retrieve the KYC record for the given user ID
    $kyc = Kyc::where('user_id', $userId)->firstOrFail();
    $user = $kyc->user; // Assuming there's a relationship between KYC and User

    // Update the KYC status and status description
    $kyc->status = $validated['status'];
    $kyc->description = $validated['description'] ?? null;
    $kyc->save();

    // Check if status is 'approved' and handle business/sponsor insertion or update
    if ($validated['status'] === 'approved') {
        if ($user->usertype === 'merchant') {
            // Insert into businesses table
            Business::updateOrCreate(
                ['owner_id' => $user->id], // Ensure no duplicate businesses for the same owner
                [
                    'business_name'       => $kyc->company_name,
                    'owner_id'            => $user->id,
                    'business_address'    => $kyc->address ?? null, // Assuming address exists in KYC
                    'business_email'      => $user->email,
                    'vat_id'              => $kyc->vat_id ?? null, // Assuming VAT ID exists in KYC
                    'registration_number' => $kyc->business_registration_number,
                    'type'                => 'merchant',
                    'description'         => $kyc->description,
                ]
            );
        } elseif ($user->usertype === 'sponsor') {
            // Update sponsor table
            Sponsor::updateOrCreate(
                ['user_id' => $user->id], 
	[
		    'user_id'  => $user->id,
                    'sponsor_name'  => $kyc->company_name,
                    'sponsor_registration_number' => $kyc->business_registration_number,
                    'sponsor_description'  => $kyc->description,
                ]
            );
        }
    }

    // Send email to the user
    $this->sendKycStatusEmail($user->email, $kyc);

    // Return the updated KYC object
    return response()->json([
        'success' => true,
        'message' => 'KYC status updated successfully.',
        'data' => $kyc,
    ], 200);
}





protected function sendKycStatusEmail(string $email, Kyc $kyc): void
{
    $subject = 'KYC Status Update';
    $data = [
        'status' => $kyc->status,
        'description' => $kyc->status_description,
    ];

    Mail::send('Email.kyc_status_updated', $data, function ($mail) use ($email, $subject) {
        $mail->to($email)
             ->subject($subject);
    });
}


/**
 * Fetch all users with their KYC data where KYC table row exists for the user.
 *
 * @param int $perPage
 * @param int $page
 * @return JsonResponse
 */
public function getAllUsersWithKycc(int $perPage = 10, int $page = 1): JsonResponse
{
    // Retrieve only users with KYC data and paginate
    $users = User::whereHas('kyc')
        ->with('kyc')
        ->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated data as JSON response
    return response()->json([
        'success' => true,
        'message' => 'Users with KYC data retrieved successfully.',
        'data' => [
            'users' => $users->items(), // Current page items
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
        ],
    ], 200);
}


public function getAllUsersWithKyc(Request $request): JsonResponse
{
    $perPage = $request->query('per_page', 10); // Default to 10 if not provided

    // Laravel will automatically handle 'page' from the query string
    $users = User::with('kyc')->latest()->paginate($perPage);

    return response()->json([
        'success' => true,
        'message' => 'Users retrieved successfully.',
        'data' => [
            'users' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
        ],
    ], 200);
}



public function getUserByIdOrEmail($identifier): JsonResponse
{
    // Check if the identifier is numeric to determine if it is an ID
    $query = User::with('kyc');

    if (is_numeric($identifier)) {
        // Search by ID if the identifier is numeric
        $query->where('id', $identifier);
    } else {
        // Search by email otherwise
        $query->where('email', $identifier);
    }

    // Retrieve the user
    $user = $query->first();

    // Check if the user exists
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 404);
    }

    // Return the user data, including related KYC data
    return response()->json([
        'success' => true,
        'message' => 'User retrieved successfully.',
        'data' => $user->toArray(),
    ], 200);
}


}
