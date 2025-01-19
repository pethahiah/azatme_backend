<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Feedback;
use App\Inquiry;
use Auth;
use App\Mail\ComplaintNotification;
use App\Mail\InquiryMail;
use Mail;
use App\Http\Resources\CommentResource;
use App\Comment;
use App\Reply;
use App\Http\Resources\ReplyResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class ComplainController extends Controller
{
    /**
     * @group Complaint
     *
     * API endpoints for user complaint.
     *
     * Create a new complaint.
     *
     * This endpoint allows authenticated users to create a new complaint.
     *
     * @bodyParam expense_name string required The name of the expense related to the complaint. Example: "Office Supplies"
     * @bodyParam description string required A detailed description of the complaint. Example: "The invoice for office supplies was incorrect."
     * @bodyParam complain_reference_code string required A reference code for the complaint. Example: "INV123456"
     * @bodyParam severity string required The severity of the complaint. Example: "high"
     *
     * @response 200 {
     *   "id": 1,
     *   "expense_name": "Office Supplies",
     *   "description": "The invoice for office supplies was incorrect.",
     *   "severity": "high",
     *   "user_id": 1,
     *   "status": "progress",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "expense_name": ["The expense name field is required."],
     *     "description": ["The description field is required."],
     *     "complain_reference_code": ["The complain reference code field is required."],
     *     "severity": ["The severity field is required."]
     *   }
     * }
     *
     * @post /makeComplain
     */

public function makeComplain(Request $request)
{
    // Check if user is authenticated before accessing their ID
//	dd(Auth::user()->email);
  	$userId = Auth::user()->id;
    // Create the complaint
    $complain = Feedback::create([
        'expense_name' => $request->expense_name,
        'description' => $request->description,
        'complain_reference_code' => Str::random(10),
        'severity' => $request->severity,
        'user_id' => $userId,
	'status' => 'progress',
    ]);

    // Send email to admin
    $adminEmail = 'adunola.adeyemi@gmail.com';
    $data = [
        'expense_name' => $request->expense_name,
        'description' => $request->description,
        'severity' => $request->severity,
        'user_name' => $userId ? Auth::user()->name : 'Guest',
    ];

    // Assuming you have the ComplaintNotification class correctly implemented
    Mail::to($adminEmail)->send(new ComplaintNotification($data));

    return response()->json($complain);
}

    /**
     * Get all complaints made by the authenticated user.
     * @group Complaint
     * This endpoint retrieves a paginated list of complaints made by the authenticated user.
     *
     * @queryParam per_page int The number of results per page. Defaults to 10. Example: 10
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "expense_name": "Office Supplies",
     *       "description": "The invoice for office supplies was incorrect.",
     *       "complain_reference_code": "INV123456",
     *       "severity": "high",
     *       "status": "progress",
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     },
     *     // More complaint objects
     *   ],
     *   "first_page_url": "http://example.com/get-complains-per-user?page=1",
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/get-complains-per-user?page=1",
     *   "next_page_url": null,
     *   "path": "http://example.com/get-complains-per-user",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "total": 1
     * }
     *
     * @response 404 {
     *   "message": "No complaints found for this user"
     * }
     *
     * @get /get-complains-per-user
     */

public function getComplainsPerUser(Request $request)
{
    $perPage = $request->query('per_page', 10);

    $getAllComplains = Feedback::where('user_id', Auth::user()->id)
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

    if ($getAllComplains->isNotEmpty()) {
        return response()->json($getAllComplains);
    } else {
        return response()->json([
            'message' => 'No complaints found for this user'
        ], 404);
    }
}


    /**
     * Get a specific feedback by ID.
     * @group Complaint
     * This endpoint retrieves a specific feedback and its associated comments.
     *
     * @urlParam feedbackId integer required The ID of the feedback to retrieve. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "content": "Comment content",
     *       "replies": [
     *         {
     *           "id": 1,
     *           "content": "Reply content"
     *         }
     *       ]
     *     }
     *     // More comments
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Feedback] with id 1"
     * }
     *
     * @get /feedback-by-id/{feedbackId}
     */

 public function getFeedbackById($feedbackId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comments = $feedback->comments()->with('replies')->get();

        return CommentResource::collection($comments);
    }


    /**
     * Create a new comment on a specific feedback.
     * @group Complaint
     * This endpoint allows users to create a comment on a specific feedback.
     *
     * @urlParam feedbackId integer required The ID of the feedback to comment on. Example: 1
     * @bodyParam content string required The content of the comment. Example: "This issue needs immediate attention."
     *
     * @response 201 {
     *   "id": 1,
     *   "user_id": 1,
     *   "content": "This issue needs immediate attention.",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "content": ["The content field is required."]
     *   }
     * }
     *
     * @post /create/comment/{feedbackId}
     */
    public function storeComment(Request $request, $feedbackId)
    {
	$user = Auth::user();
        $feedback = Feedback::findOrFail($feedbackId);

        $request->validate([
            'content' => 'required',
        ]);

        $comment = $feedback->comments()->create([
	     'user_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        return new CommentResource($comment);
    }

    /**
     * Retrieve a single comment from a specific feedback.
     * @group Complaint
     * This endpoint retrieves a single comment from a specific feedback.
     *
     * @urlParam feedbackId integer required The ID of the feedback. Example: 1
     * @urlParam commentId integer required The ID of the comment to retrieve. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "content": "Comment content",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Comment] with id 1"
     * }
     *
     * @get /show/comment/{feedbackId}/{commentId}
     */
public function showComment($feedbackId, $commentId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comment = $feedback->comments()->findOrFail($commentId);

        return new CommentResource($comment);
    }

    /**
     * Update an existing comment on a specific feedback.
     * @group Complaint
     * This endpoint allows users to update a comment on a specific feedback.
     *
     * @urlParam feedbackId integer required The ID of the feedback. Example: 1
     * @urlParam commentId integer required The ID of the comment to update. Example: 1
     * @bodyParam content string required The updated content of the comment. Example: "Updated comment content."
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "content": "Updated comment content.",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "content": ["The content field is required."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Comment] with id 1"
     * }
     *
     * @put /update/comment/{feedbackId}/{commentId}
     */
    public function updateComment(Request $request, $feedbackId, $commentId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comment = $feedback->comments()->findOrFail($commentId);

        $request->validate([
            'content' => 'required',
        ]);

        $comment->update([
            'content' => $request->input('content'),
        ]);

        return new CommentResource($comment);
    }


    /**
     * Delete a specific comment from a feedback.
     * @group Complaint
     * This endpoint allows users to delete a specific comment from a feedback.
     *
     * @urlParam feedbackId integer required The ID of the feedback. Example: 1
     * @urlParam commentId integer required The ID of the comment to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Comment deleted successfully"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Comment] with id 1"
     * }
     *
     * @delete /delete/comment/{feedbackId}/{commentId}
     */
    public function destroyComment($feedbackId, $commentId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comment = $feedback->comments()->findOrFail($commentId);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }




    /**
     * Create a reply to a specific comment.
     * @group Complaint
     * This endpoint allows users to create a reply to a specific comment.
     *
     * @urlParam commentId integer required The ID of the comment to reply to. Example: 1
     * @bodyParam content string required The content of the reply. Example: "Thanks for your feedback."
     *
     * @response 201 {
     *   "id": 1,
     *   "user_id": 1,
     *   "content": "Thanks for your feedback.",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "content": ["The content field is required."]
     *   }
     * }
     *
     * @post /create/reply/{commentId}
     */
public function storeReply(Request $request, $commentId)
    {
	$user = Auth::user();
        $comment = Comment::findOrFail($commentId);

        $request->validate([
            'content' => 'required',
        ]);

        $reply = $comment->replies()->create([
            'content' => $request->input('content'),
	     'user_id' => $user->id,
        ]);

        return new ReplyResource($reply);
    }



    /**
     * Update a reply to a specific comment.
     * @group Complaint
     * This endpoint allows users to update a reply to a specific comment.
     *
     * @urlParam commentId integer required The ID of the comment. Example: 1
     * @urlParam replyId integer required The ID of the reply to update. Example: 1
     * @bodyParam content string required The updated content of the reply. Example: "Updated reply content."
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "content": "Updated reply content.",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "content": ["The content field is required."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Reply] with id 1"
     * }
     *
     * @put /update/reply/{replyId}/{commentId}
     */
public function updateReply(Request $request, $commentId, $replyId)
    {
        $comment = Comment::findOrFail($commentId);
        $reply = $comment->replies()->findOrFail($replyId);

        $request->validate([
            'content' => 'required',
        ]);

        $reply->update([
            'content' => $request->input('content'),
        ]);

        return new ReplyResource($reply);
    }


    /**
     * Delete a specific reply from a comment.
     * @group Complaint
     * This endpoint allows users to delete a specific reply from a comment.
     *
     * @urlParam commentId integer required The ID of the comment. Example: 1
     * @urlParam replyId integer required The ID of the reply to delete. Example: 1
     *
     * @response 200 {
     *   "message": "Reply deleted successfully"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [Reply] with id 1"
     * }
     *
     * @delete /delete/comment/{replyId}/{commentId}
     */
    public function destroyReply($commentId, $replyId)
    {
        $comment = Comment::findOrFail($commentId);
        $reply = $comment->replies()->findOrFail($replyId);

        $reply->delete();

        return response()->json(['message' => 'Reply deleted successfully']);
    }

    /**
     * Create a new inquiry.
     * @group Complaint
     * This endpoint allows users to create a new inquiry.
     *
     * @bodyParam first_name string required The first name of the user making the inquiry. Example: "John"
     * @bodyParam last_name string required The last name of the user making the inquiry. Example: "Doe"
     * @bodyParam phone_number string required The phone number of the user. Example: "+123456789"
     * @bodyParam issue string required The issue being inquired about. Example: "Unable to access account"
     * @bodyParam email string required The email address of the user. Example: "john.doe@example.com"
     *
     * @response 201 {
     *   "id": 1,
     *   "first_name": "John",
     *   "last_name": "Doe",
     *   "phone_number": "+123456789",
     *   "issue": "Unable to access account",
     *   "email": "john.doe@example.com",
     *   "created_at": "2024-08-20T12:00:00.000000Z",
     *   "updated_at": "2024-08-20T12:00:00.000000Z"
     * }
     *
     * @response 400 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "first_name": ["The first name field is required."],
     *     "last_name": ["The last name field is required."],
     *     "phone_number": ["The phone number field is required."],
     *     "issue": ["The issue field is required."],
     *     "email": ["The email field is required."]
     *   }
     * }
     *
     * @post /makeInquiry
     */

public function makeInquiry(Request $request)
    {

        $issue = Inquiry::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'issue' => $request->issue,
            'email' => $request->email,
        ]);

        // Send email to admin
        $adminEmail = 'support@pethahiah.com';
        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'issue' => $request->issue,
            'email' => $request->email,
        ];

        // Assuming you have the ComplaintNotification class correctly implemented
        Mail::to($adminEmail)->send(new InquiryMail($data));

        return response()->json($issue);
    }
    /**
     * Get all inquiries.
     * @group Complaint
     * This endpoint retrieves a paginated list of all inquiries.
     *
     * @queryParam per_page int The number of results per page. Defaults to 10. Example: 10
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "first_name": "John",
     *       "last_name": "Doe",
     *       "phone_number": "+123456789",
     *       "issue": "Unable to access account",
     *       "email": "john.doe@example.com",
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More inquiry objects
     *   ],
     *   "first_page_url": "http://example.com/getAllInquiry?page=1",
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/getAllInquiry?page=1",
     *   "next_page_url": null,
     *   "path": "http://example.com/getAllInquiry",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "total": 1
     * }
     *
     * @get /getAllInquiry
     */

public function getAllInquiry(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $getAllInquiry = Inquiry::paginate($perPage);
    return $getAllInquiry;
}

    /**
     * Get all complaints.
     * @group Complaint
     * This endpoint retrieves a paginated list of all complaints.
     *
     * @queryParam per_page int The number of results per page. Defaults to 10. Example: 10
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "expense_name": "Office Supplies",
     *       "description": "The invoice for office supplies was incorrect.",
     *       "complain_reference_code": "INV123456",
     *       "severity": "high",
     *       "status": "progress",
     *       "created_at": "2024-08-20T12:00:00.000000Z",
     *       "updated_at": "2024-08-20T12:00:00.000000Z"
     *     }
     *     // More complaint objects
     *   ],
     *   "first_page_url": "http://example.com/getAllComplains?page=1",
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/getAllComplains?page=1",
     *   "next_page_url": null,
     *   "path": "http://example.com/getAllComplains",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "total": 1
     * }
     *
     * @get /getAllComplains
     */

public function getAllComplains(Request $request)
        {
	    $perPage = $request->input('per_page', 10);
            $getAllComplains = Feedback::paginate($perPage);
            return $getAllComplains;
        }


}
