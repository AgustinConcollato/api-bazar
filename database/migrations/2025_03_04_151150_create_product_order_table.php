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
        Schema::create('product_order', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->longText('picture')->nullable();
            $table->decimal('purchase_price', 11, 2)->default(0);
            $table->decimal('price', 11, 2);
            $table->uuid('product_id');
            $table->uuid('order_id');
            $table->integer('quantity');
            $table->integer('discount')->nullable();
            $table->decimal('subtotal', 11, 2);

            // Definir claves foráneas
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // Agregar timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_order');
    }
};
