<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\AdminReply;
use App\Models\UserReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class FeedbackService
{


	public function getAllFeedbacks(Request $request)
{
    // Check if the user is authenticated
    if (Auth::check()) {
        // Access the authenticated user's id
        $userId = Auth::id();
        
        // Use the user id to retrieve feedbacks
        return Feedback::where('user_id', $userId)->paginate($request->query('per_page', 10));
    } else {
        // Handle the case where the user is not authenticated
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}


    public function storeFeedback(array $data)
    {
        return Feedback::create($data);
    }

    public function getFeedbackById($id)
    {
        return Feedback::findOrFail($id);
    }

    public function updateFeedback($id, array $data)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update($data);
        return $feedback;
    }

    public function deleteFeedback($id)
    {
        Feedback::findOrFail($id)->delete();
    }

    public function replyToFeedback($feedbackId, $comment)
    {
        $feedback = Feedback::findOrFail($feedbackId);

        return AdminReply::create([
            'feedback_id' => $feedback->id,
            'admin_id' => Auth::User()->id,
            'description' => $comment,
            'user_id' => $feedback->user_id,
        ]);
    }

    public function userReplyToAdmin($replyId, $comment)
    {
        $reply = AdminReply::where('feedback_id', $replyId)->first();

        return UserReply::create([
            'admin_id' => $reply->id,
            'feedback_id' => $reply->feedback_id,
            'description' => $comment,
        ]);
    }
}
