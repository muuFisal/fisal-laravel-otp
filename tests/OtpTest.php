<?php

namespace Fisal\Otp\Tests;

use Carbon\Carbon;
use Fisal\Otp\Models\Otp;

class OtpTest extends TestCase
{
    public function test_generate_creates_one_valid_otp_per_identifier_and_type(): void
    {
        $otp = new \Fisal\Otp\Otp();

        $otp->generate('user-1', 'numeric', 4, 10, 'login');
        $otp->generate('user-1', 'numeric', 4, 10, 'login'); // should delete previous valid

        $this->assertSame(1, Otp::query()->where('identifier','user-1')->where('otp_type','login')->count());
        $this->assertTrue(Otp::query()->first()->valid);
    }

    public function test_validate_consumes_on_success(): void
    {
        $svc = new \Fisal\Otp\Otp();
        $res = $svc->generate('user-2', 'numeric', 4, 10, '2fa');

        $ok = $svc->validate('user-2', $res->token, '2fa');
        $this->assertTrue($ok->status);

        $this->assertSame(0, Otp::query()->where('identifier','user-2')->where('otp_type','2fa')->count());
    }

    public function test_validate_respects_attempts_limit(): void
    {
        config()->set('otp.max_attempts', 2);

        $svc = new \Fisal\Otp\Otp();
        $res = $svc->generate('user-3', 'numeric', 4, 10, 'login');

        $fail1 = $svc->validate('user-3', '0000', 'login');
        $this->assertFalse($fail1->status);
        $this->assertSame(1, Otp::query()->where('identifier','user-3')->where('otp_type','login')->first()->attempts);

        $fail2 = $svc->validate('user-3', '0000', 'login');
        $this->assertFalse($fail2->status);

        $row = Otp::query()->where('identifier','user-3')->where('otp_type','login')->first();
        $this->assertFalse((bool) $row->valid);
    }

    public function test_validate_fails_when_expired(): void
    {
        $svc = new \Fisal\Otp\Otp();
        $res = $svc->generate('user-4', 'numeric', 4, 1, 'login');

        $row = Otp::query()->where('identifier','user-4')->where('otp_type','login')->first();
        $row->created_at = Carbon::now()->subMinutes(5);
        $row->save();

        $out = $svc->validate('user-4', $res->token, 'login');
        $this->assertFalse($out->status);
        $this->assertSame('OTP Expired', $out->message);
    }
}
