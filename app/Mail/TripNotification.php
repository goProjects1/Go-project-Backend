<?php

namespace App\Mail;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TripNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): TripNotification
    {
        return $this
            ->subject('Notification')
            ->view('Email.creatorNotify', ['trip' => $this->trip]);
    }
}
