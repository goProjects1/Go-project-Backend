<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class UserReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'feedback_id',
        'admin_id',

    ];
}
