<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static findOrFail($feedbackId)
 */
class Feedback extends Model
{
    use HasFactory;
    protected $fillable = [

        'description',
        'user_id',
        'reference_code',
        'rating',
        'severity',
        'status'
    ];

}
