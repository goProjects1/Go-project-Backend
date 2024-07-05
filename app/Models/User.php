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
 * @method static find($user_id)
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
        'hasReferral',
        'referral_url',
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

    public function sentTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trip::class, 'sender_id');
    }


    public function receivedTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trip::class, 'guest_id');
    }

    public function trips(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Trip::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
