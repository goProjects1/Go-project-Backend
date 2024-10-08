<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail($tripId)
 * @method static where(string $string, mixed $tripId)
 * @property mixed $sender_id
 * @property mixed $variable_distance
 * @property mixed $unique_code
 */
class Trip extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    public $name;
    /**
     * @var mixed
     */
    public $description;
    protected $fillable = [
        'type',
        'pickUp',
        'destination',
        'sender_id',
        'guest_id',
        'variable_distance',
        'meeting_point',
        'fee_option',
        'latitude',
        'longitude',
        'destLongitude',
        'destLatitude',
        'charges',
        'fee_amount',
        'description',
        'property_id',
        'load_option',
        'load_in_kg',
        'number_of_guest',
        'available_seat',
        'trip_status',
        'distance',
        'journey_status',
    ];

    // Relationship: A trip belongs to a user as the sender
    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relationship: A trip belongs to a user as the guest
    public function guest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

}
