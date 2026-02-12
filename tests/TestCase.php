<?php

namespace Fisal\Otp\Tests;

use Fisal\Otp\OtpServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OtpServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Otp' => \Fisal\Otp\Otp::class,
        ];
    }
}
