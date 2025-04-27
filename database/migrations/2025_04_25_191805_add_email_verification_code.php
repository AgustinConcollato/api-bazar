<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email_verification_code')->nullable()->after('email');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('email_verification_code');
            $table->dropColumn('email_verification_expires_at');
        });
    }
};
