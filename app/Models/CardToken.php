<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'email', 'customerId', 'cardTokenId'
    ];
}
