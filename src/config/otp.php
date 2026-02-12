<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max attempts
    |--------------------------------------------------------------------------
    | Maximum number of failed attempts allowed before OTP becomes invalid.
    */
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),

];
