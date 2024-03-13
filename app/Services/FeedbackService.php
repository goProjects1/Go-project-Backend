<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\AdminReply;
use App\Models\UserReply;

class FeedbackService
{

    public function getAllFeedbacks()
    {
        return Feedback::all();
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
            'description' => $comment,
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
