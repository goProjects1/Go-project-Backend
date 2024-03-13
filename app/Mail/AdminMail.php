<?php

namespace App\Mail;

use App\Models\UserReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use app\Models\AdminReply;

class AdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminReply;

    public function __construct(AdminReply $adminReply)
    {
        $this->adminReply = $adminReply;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): AdminMail
    {
        return $this->subject('Feedback Notification')
            ->view('Email.adminFeedback', ['adminReply' => $this->adminReply]);
    }
}
