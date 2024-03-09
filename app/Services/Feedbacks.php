<?php

namespace App\Services;


use App\Models\Feedback;
use App\Models\AdminReply;
use App\Models\UserReply;

class Feedbacks
{
    public function storeFeedback(array $data)
    {
        $feedback = Feedback::create([
            'user_id' => $data['user_id'],
            'description' => $data['description'],
            'rating' => $data['rating'],
            'status' => $data['status'],
            'severity' => $data['severity']
        ]);

        // Implement your email sending logic here

        return $feedback;
    }

    public function replyToFeedback($feedbackId, $adminComment)
    {
        $feedback = Feedback::findOrFail($feedbackId);

        $adminReply = AdminReply::create([
            'feedback_id' => $feedback->id,
            'description' => $adminComment,
        ]);

        // Implement your email sending logic here

        return $adminReply;
    }

    public function userReplyToAdmin($adminReplyId, $userReply)
    {
        $adminReply = AdminReply::findOrFail($adminReplyId);

        $userReply = UserReply::create([
            'admin_reply_id' => $adminReply->id,
            'description' => $userReply,
        ]);

        // Implement your email sending logic here

        return $userReply;
    }
}
