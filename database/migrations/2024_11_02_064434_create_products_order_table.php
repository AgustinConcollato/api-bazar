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
        Schema::create('products_order', function (Blueprint $table) {
            $table->string('name', 100);
            $table->longText('picture');
            $table->integer('price');
            $table->string('code', 25);
            $table->string('product_id', 25);
            $table->string('order_id', 25);
            $table->integer('quantity');
            $table->integer('subtotal');
            $table->integer('count', true);
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
