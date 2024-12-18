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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id', 25);
            $table->string('client', 100);
            $table->string('client_name', 100);
            $table->string('status', 25);
            $table->string('comment', 300)->nullable();
            $table->integer('total_amount');
            $table->bigInteger('date');
            $table->integer('count', true);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
