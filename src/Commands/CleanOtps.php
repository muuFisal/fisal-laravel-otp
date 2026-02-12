<?php

namespace Fisal\Otp\Commands;

use Carbon\Carbon;
use Fisal\Otp\Models\Otp;
use Illuminate\Console\Command;

class CleanOtps extends Command
{
    protected $signature = 'otp:clean';

    protected $description = 'Clean OTP database, remove expired or invalid OTPs.';

    public function handle(): int
    {
        try {
            $now = Carbon::now();

            // Delete invalid tokens
            $invalidCount = Otp::query()->where('valid', false)->delete();

            // Delete expired tokens (still marked valid but expired by created_at + validity)
            $expiredCount = 0;

            $validOtps = Otp::query()->where('valid', true)->get();
            foreach ($validOtps as $otp) {
                $validUntil = (clone $otp->created_at)->addMinutes((int) $otp->validity);
                if ($now->greaterThan($validUntil)) {
                    $otp->delete();
                    $expiredCount++;
                }
            }

            $this->info("Invalid tokens deleted: {$invalidCount}");
            $this->info("Expired tokens deleted: {$expiredCount}");

            return 0;
        } catch (\Throwable $e) {
            $this->error("Error:: {$e->getMessage()}");
            return 1;
        }
    }
}
