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
        Schema::create('products_order', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->string('name', 100);
            $table->longText('picture');
            $table->decimal('price', 11);
            $table->uuid('product_id');
            $table->uuid('order_id');
            $table->integer('quantity');
            $table->integer('discount')->nullable();
            $table->decimal('subtotal', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_order');
    }
};
