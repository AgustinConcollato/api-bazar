<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('user_addresses', 'client_address');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('client_address', 'user_addresses');
    }
};
