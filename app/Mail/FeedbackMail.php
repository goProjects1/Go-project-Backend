<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\Feedback;

class FeedbackMail extends Mailable
{
    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function build(): FeedbackMail
    {
        return $this->subject('Feedback Notification')
            ->view('Email.userFeedback', ['feedback' => $this->feedback]);
    }
}
