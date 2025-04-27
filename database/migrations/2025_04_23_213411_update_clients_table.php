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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->unique()->after('name');
            $table->string('phone_number')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('phone_number');
            $table->string('password')->after('email_verified_at');
            $table->rememberToken()->after('password');
            
            // Opcional: establecer el id como clave primaria si no lo hiciste antes
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'phone_number',
                'email_verified_at',
                'password',
                'remember_token',
            ]);
        });
    }
        
};
