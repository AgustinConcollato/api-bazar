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
            $table->string('client_id', 25);
            $table->string('status', 25);
            $table->string('comment', 200);
            $table->bigInteger('date');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_type', 25)->default('efectivo');
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
