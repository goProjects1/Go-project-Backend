<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Mail\AdminMail;
use App\Mail\UserReplyMail;
use App\Models\AdminReply;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\FeedbackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackMail;

class FeedbackController extends BaseController
{
    protected $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {

        $this->feedbackService = $feedbackService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $feedbacks = $this->feedbackService->getAllFeedbacks($request);
        return response()->json(['message' => 'Success', 'data' => $feedbacks], 200);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'severity' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'progress';

        $feedback = $this->feedbackService->storeFeedback($validated);
        $adm = "projectgo295@gmail.com";
        Mail::to($adm)->send(new FeedbackMail($feedback));

        return response()->json(['message' => 'Feedback submitted successfully', 'data' => $feedback], 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $feedback = $this->feedbackService->updateFeedback($id, $request->all());
            return $this->sendResponse($feedback, 'Feedback updated successfully', 200);
        } catch (\Exception $e) {
            return $this->sendError('Error updating feedback.', $e->getMessage(), 500);
        }
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->feedbackService->deleteFeedback($id);
            return $this->sendResponse([], 'Feedback deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->sendError('Error deleting feedback.', $e->getMessage(), 500);
        }
    }


    public function show($id): \Illuminate\Http\JsonResponse
    {
        $feedback = $this->feedbackService->getFeedbackById($id);
        return response()->json(['message' => 'Success', 'data' => $feedback], 200);
    }

    public function reply(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['description' => 'required|string']);
        $adminReply = $this->feedbackService->replyToFeedback($id, $validated['description']);
        $userFeedbackId = Feedback::where('id', $id)->first();
        $userFeedbackUserId = $userFeedbackId->user_id;
        $userFeedbackEmail = User::where('id', $userFeedbackUserId)->first()->email;
        Mail::to($userFeedbackEmail)->send(new AdminMail($adminReply));
        return response()->json(['message' => 'Admin reply submitted successfully', 'data' => $adminReply], 201);
    }

    public function AdminReplies($id): \Illuminate\Http\JsonResponse
    {
        $feedback = AdminReply::where('feedback_id', $id)->first();
        return response()->json(['message' => 'Success', 'data' => $feedback], 200);
    }

    public function AdminReplyPerUser(): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::user()->getAuthIdentifier();
        $feedback = AdminReply::where('user_id', $userId)->get();
        return response()->json(['message' => 'Success', 'data' => $feedback], 200);
    }

    public function reply(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['description' => 'required|string']);
        $adminReply = $this->feedbackService->replyToFeedback($id, $validated['description']);
        $adm = "projectgo295@gmail.com";
        Mail::to($adm)->send(new AdminMail($adminReply));
        return response()->json(['message' => 'Admin reply submitted successfully', 'data' => $adminReply], 201);
    }


    public function userReply(Request $request, $feedback_id, $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['description' => 'required|string']);
        $userReply = $this->feedbackService->userReplyToAdmin($id, $validated['description']);
        $adm = "projectgo295@gmail.com";
        Mail::to($adm)->send(new userReplyMail($userReply));
        return response()->json(['message' => 'User reply submitted successfully', 'data' => $userReply], 201);
    }
}
