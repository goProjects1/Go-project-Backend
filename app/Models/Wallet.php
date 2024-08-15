<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'charges',
        'residual_amount',
        'amount_paid_by_paythru',
        'balance',
        'amountExpectedRefundMe',
        'amountExpectedKontribute',
        'amountExpectedBusiness',

    ];
}
