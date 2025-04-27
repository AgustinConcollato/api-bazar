<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change(); // hacerlo nullable
            $table->dropUnique(['email']); // eliminar índice actual si existe
        });

        // Crear índice único compuesto con SQL bruto (porque Laravel no soporta NULL con índices únicos compuestos)
        DB::statement('CREATE UNIQUE INDEX clients_email_source_unique ON clients (email, source)');
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_email_source_unique');
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
        });
    }
};



