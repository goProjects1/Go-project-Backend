<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verifysms extends Model
{
    use HasFactory;

    protected $dates = ['otp_expires_time'];

    protected $fillable = [
        'phone',
        'user_id',
        'otp',
        'email',
        'medium',
        'otp_expires_time'

    ];
}
