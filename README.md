# fisal/laravel-otp

A lightweight Laravel package to generate and validate one-time passwords (OTPs).

This is a maintained fork intended for real projects: it adds **OTP type binding** (e.g. `login`, `2fa`) and an **attempts limit**.

## Installation

```bash
composer require fisal/laravel-otp:^1.0
```

## Configuration (optional)

Publish config (optional):

```bash
php artisan vendor:publish --tag=otp-config
```

Then you can control max attempts via:

- `config/otp.php`
- or env: `OTP_MAX_ATTEMPTS=5`

## Usage

### Generate OTP

```php
use Otp;

// tokenType: numeric | alpha_numeric
// otpType: purpose binding (login | 2fa | reset_password | ...)
$result = Otp::generate(
    identifier: '201001234567',
    tokenType: 'numeric',
    length: 6,
    validity: 5,
    otpType: 'login'
);

$token = $result->token;
```

### Validate OTP (consumes on success)

```php
$result = Otp::validate(
    identifier: '201001234567',
    token: '123456',
    otpType: 'login'
);

if ($result->status) {
    // valid
} else {
    // invalid / expired
    // optional: $result->remaining_attempts
}
```

### Boolean check (does NOT consume)

```php
$isValid = Otp::isValid('201001234567', '123456', 'login');
```

## Artisan

Clean invalid and expired OTPs:

```bash
php artisan otp:clean
```

## License

MIT
