<?php

namespace Fisal\Otp;

use Carbon\Carbon;
use Exception;
use Fisal\Otp\Models\Otp as Model;
use Illuminate\Support\Facades\DB;

class Otp
{
    /**
     * Generate a new OTP.
     *
     * @param string $identifier  Anything you want to bind OTP to (phone/email/user_id/etc.)
     * @param string $tokenType   Token generation type: numeric|alpha_numeric
     * @param int    $length      OTP length
     * @param int    $validity    Validity minutes
     * @param string $otpType     Purpose/type: e.g. login|2fa|password_reset (bind validation to this type)
     * @return object
     * @throws Exception
     */
    public function generate(
        string $identifier,
        string $tokenType = 'numeric',
        int $length = 4,
        int $validity = 10,
        string $otpType = 'default'
    ): object {
        // Remove any previous valid OTPs for the same identifier + otpType (one active OTP per purpose)
        Model::query()
            ->where('identifier', $identifier)
            ->where('otp_type', $otpType)
            ->where('valid', true)
            ->delete();

        switch ($tokenType) {
            case 'numeric':
                $token = $this->generateNumericToken($length);
                break;
            case 'alpha_numeric':
                $token = $this->generateAlphanumericToken($length);
                break;
            default:
                throw new Exception("{$tokenType} is not a supported token type");
        }

        Model::create([
            'identifier' => $identifier,
            'otp_type'   => $otpType,
            'token'      => $token,
            'validity'   => $validity,
            'attempts'   => 0,
            'valid'      => true,
        ]);

        return (object) [
            'status'  => true,
            'token'   => $token,
            'message' => 'OTP generated',
        ];
    }

    /**
     * Quick boolean check (does NOT consume).
     */
    public function isValid(string $identifier, string $token, string $otpType = 'default'): bool
    {
        $otp = Model::query()
            ->where('identifier', $identifier)
            ->where('otp_type', $otpType)
            ->where('valid', true)
            ->latest('id')
            ->first();

        if (! ($otp instanceof Model)) {
            return false;
        }

        if (! $this->isNotExpired($otp)) {
            $otp->update(['valid' => false]);
            return false;
        }

        if (! hash_equals((string) $otp->token, (string) $token)) {
            return false;
        }

        return true;
    }

    /**
     * Validate OTP with:
     * - expiry check
     * - attempts limit (config: otp.max_attempts, default 5)
     * - type binding via $otpType
     * - consume on success (delete record)
     */
    public function validate(string $identifier, string $token, string $otpType = 'default'): object
    {
        $maxAttempts = (int) config('otp.max_attempts', 5);
        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }

        // Atomic validation to reduce race conditions
        return DB::transaction(function () use ($identifier, $token, $otpType, $maxAttempts) {

            /** @var Model|null $otp */
            $otp = Model::query()
                ->where('identifier', $identifier)
                ->where('otp_type', $otpType)
                ->where('valid', true)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! ($otp instanceof Model)) {
                return (object) [
                    'status'  => false,
                    'message' => 'OTP does not exist',
                ];
            }

            // Expired?
            if (! $this->isNotExpired($otp)) {
                $otp->update(['valid' => false]);
                return (object) [
                    'status'  => false,
                    'message' => 'OTP Expired',
                ];
            }

            // Token mismatch -> attempts + invalidate if exceeded
            if (! hash_equals((string) $otp->token, (string) $token)) {
                $otp->attempts = ((int) $otp->attempts) + 1;

                $remaining = max(0, $maxAttempts - (int) $otp->attempts);

                if ((int) $otp->attempts >= $maxAttempts) {
                    $otp->valid = false;
                }

                $otp->save();

                return (object) [
                    'status'             => false,
                    'message'            => 'OTP is not valid',
                    'remaining_attempts' => $remaining,
                ];
            }

            // Success: consume OTP
            $otp->delete();

            return (object) [
                'status'  => true,
                'message' => 'OTP is valid',
            ];
        });
    }

    private function isNotExpired(Model $otp): bool
    {
        $now = Carbon::now();
        $validityUntil = (clone $otp->created_at)->addMinutes((int) $otp->validity);

        return $now->lessThanOrEqualTo($validityUntil);
    }

    /**
     * Generate numeric token.
     *
     * @throws Exception
     */
    private function generateNumericToken(int $length = 4): string
    {
        $i = 0;
        $token = '';

        while ($i < $length) {
            $token .= random_int(0, 9);
            $i++;
        }

        return $token;
    }

    private function generateAlphanumericToken(int $length = 4): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($characters), 0, $length);
    }
}
