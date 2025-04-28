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

            $table->dropColumn('code');

            $table->uuid('id')->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_address', function (Blueprint $table) {
            // Agregar la columna 'code' de nuevo
            $table->string('code', 25);

            // Eliminar la clave primaria actual

            // Cambiar la columna 'id' a auto-incrementable
            $table->bigIncrements('id')->change();
        });
    }
};
