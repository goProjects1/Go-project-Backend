<?php

namespace App\Mail;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TripMail extends Mailable
{
    use Queueable, SerializesModels;

    public $trip;
    public $inviteLink;
    public $registrationNo;
    public $type;
    public $model;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($trip, $inviteLink, $registrationNo, $type, $name, $model)
    {
        $this->trip = $trip;
        $this->inviteLink = $inviteLink;
        $this->registrationNo = $registrationNo;
        $this->type = $type;
        $this->name = $name;
        $this->model = $model;
    }

    public function build(): TripMail
    {
        return $this
            ->subject('Notification')
            ->view('Email.trip', [
                'trip' => $this->trip,
                'inviteLink' => $this->inviteLink,
                'registrationNo' => $this->registrationNo,
                'type' => $this->type,
                'driver_name' => $this->name,
                'model' => $this->model
            ]);
    }
}
