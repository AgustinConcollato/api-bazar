<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->string('city', 150);
            $table->string('address', 150);
            $table->string('province', 150);
            $table->string('zip_code', 25)->nullable();
            $table->string('status', 25)->nullable();
            $table->string('code', 25);
            $table->string('address_number', 25);
            $table->id();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
