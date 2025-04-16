<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_address', function (Blueprint $table) {
            $table->renameColumn('user_id', 'client_id'); // Después renombramos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_address', function (Blueprint $table) {
            $table->renameColumn('client_id', 'user_id');
        });
    }

};
