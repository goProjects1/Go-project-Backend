<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail(mixed $property_id)
 */
class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'registration_no',
        'license_no',
        'user_id'
    ];
}
