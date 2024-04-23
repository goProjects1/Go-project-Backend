<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static findOrFail($replyId)
 * @method static where(string $string, $replyId)
 */
class AdminReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'feedback_id',
        'admin_id',
        'user_id'
    ];

}
