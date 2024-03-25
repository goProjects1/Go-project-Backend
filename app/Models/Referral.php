<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, $id)
 * @method static create(array $data)
 */
class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ref_url',
        'ref_code',
        'ref_by',
        'point',
        'product'
    ];
}
