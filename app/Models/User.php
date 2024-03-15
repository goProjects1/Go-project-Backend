<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method static where(string $string, mixed $email)
 * @method static selectRaw(string $string, array $array)
 * @method static findOrFail($userId)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'postcode',
        'street',
        'otp',
        'city',
        'county',
        'profile_image',
        'identity_card',
        'identity_card_no',
        'property_id',
        'job_id',
        'nationality',
        'country',
        'dob',
        'gender',
        'marital_status',
        'isVerify',
        'phone_number',
        'email_verified_at',
        'password',
        'longitude',
        'latitude',
        'address',
        'house_number',
        'usertype',
    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relationship: One user can have many trips as the sender
    public function sentTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trip::class, 'sender_id');
    }

    // Relationship: One user can have many trips as the guest
    public function receivedTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trip::class, 'guest_id');
    }
}
