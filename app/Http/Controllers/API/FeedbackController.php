<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Feedbacks;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends BaseController
{
    //

    private $feedbackService;

    public function __construct(Feedbacks $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'severity' => 'required',
            'status' => 'required',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        $adminP = User::where('usertype', 'admin')->first();
        $data = $request->only(['user_id', 'description', 'rating', 'severity']);
        $data->user_id = Auth::user()->getAuthIdentifier();
        $feedback = $this->feedbackService->storeFeedback($data);
        $subject = 'GOPROJECT: User Feedback';
        Mail::send('Email.feedback', $data, function ($message) use ($adminP, $request, $subject) {
            $message->to($adminP)->subject($subject);
        });
        return $this->sendResponse($feedback, 'Feedback submitted successfully');

    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'admin_comment' => 'required',
        ]);

        $adminComment = $request->input('admin_comment');

        $adminReply = $this->feedbackService->replyToFeedback($id, $adminComment);
        return $this->sendResponse($adminReply, 'Admin reply submitted successfully');


    }

    public function userReply(Request $request, $id)
    {
        $request->validate([
            'user_reply' => 'required',
        ]);

        $userReply = $request->input('user_reply');

        $userReply = $this->feedbackService->userReplyToAdmin($id, $userReply);

        return $this->sendResponse($userReply, 'User reply submitted successfully');
    }

}
