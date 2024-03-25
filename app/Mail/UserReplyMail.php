<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\UserReply;

class UserReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userReply;

    public function __construct(UserReply $userReply)
    {
        $this->userReply = $userReply;
    }

    public function build(): UserReplyMail
    {
        return $this
            ->subject('User Reply Notification')
            ->view('Email.userReply', ['userResponse' => $this->userReply]);
    }
}
