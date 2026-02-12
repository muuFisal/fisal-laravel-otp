<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpsTable extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->increments('id')->index();
            $table->string('identifier')->index();

            // Bind OTPs by purpose/type (e.g. login, 2fa, password_reset)
            $table->string('otp_type')->default('default')->index();

            $table->string('token');
            $table->integer('validity'); // minutes
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->boolean('valid')->default(true)->index();
            $table->timestamps();

            $table->index(['identifier', 'otp_type']);
            $table->index(['identifier', 'otp_type', 'token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
}
