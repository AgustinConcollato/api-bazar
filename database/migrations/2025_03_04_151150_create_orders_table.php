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
            $table->uuid('id');
            $table->uuid('client_id');
            $table->string('client_name', 100);
            $table->enum('status', ['pending', 'completed', 'cancelled', 'elaboration']);
            $table->string('comment', 300)->nullable();
            $table->json('address')->nullable();
            $table->integer('discount')->nullable();
            $table->decimal('total_amount', 11);
            $table->timestamps();
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
