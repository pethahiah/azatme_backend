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



class ComplainController extends Controller
{
    //


public function makeComplain(Request $request)
{
    // Check if user is authenticated before accessing their ID
//	dd(Auth::user()->email);
  	$userId = Auth::user()->id;
    // Create the complaint
    $complain = Feedback::create([
        'expense_name' => $request->expense_name,
        'description' => $request->description,
        'complain_reference_code' => $request->complain_reference_code,
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


  public function getComplainsPerUser(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $getAllComplains = Feedback::where('user_id',Auth::user()->id)->paginate($perPage);
        return $getAllComplains;
    }




 public function getFeedbackById($feedbackId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comments = $feedback->comments()->with('replies')->get();

        return CommentResource::collection($comments);
    }


// Create Comment
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

// Read a single Comments
public function showComment($feedbackId, $commentId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comment = $feedback->comments()->findOrFail($commentId);

        return new CommentResource($comment);
    }

// Update a comment
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


// Delete a comment
    public function destroyComment($feedbackId, $commentId)
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $comment = $feedback->comments()->findOrFail($commentId);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }




// Create Reply
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



// Update Reply
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


// Delete Reply
    public function destroyReply($commentId, $replyId)
    {
        $comment = Comment::findOrFail($commentId);
        $reply = $comment->replies()->findOrFail($replyId);

        $reply->delete();

        return response()->json(['message' => 'Reply deleted successfully']);
    }



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


public function getAllInquiry(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $getAllInquiry = Inquiry::paginate($perPage);
    return $getAllInquiry;
}



public function getAllComplains(Request $request)
        {
	    $perPage = $request->input('per_page', 10);
            $getAllComplains = Feedback::paginate($perPage);
            return $getAllComplains;
        }


}
