<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Solo para MySQL
        DB::statement("ALTER TABLE movements MODIFY COLUMN method ENUM('cash', 'check', 'transfer', 'credit_card')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE movements MODIFY COLUMN method ENUM('cash', 'check', 'transfer')");
    }
};