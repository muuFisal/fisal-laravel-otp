<?php

namespace Fisal\Otp\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'otps';

    protected $fillable = [
        'identifier',
        'otp_type',
        'token',
        'validity',
        'attempts',
        'valid',
    ];

    protected $casts = [
        'valid'    => 'boolean',
        'attempts' => 'integer',
        'validity' => 'integer',
    ];
}
