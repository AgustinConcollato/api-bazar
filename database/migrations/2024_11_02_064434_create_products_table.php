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
        Schema::create('products', function (Blueprint $table) {
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->string('category_id', 80);
            $table->string('subcategory', 80)->nullable();
            $table->integer('available_quantity')->nullable();
            $table->string('code', 25)->unique('code');
            $table->string('id', 25);
            $table->decimal('price', 9, 0);
            $table->integer('discount')->nullable();
            $table->string('status', 9);
            $table->string('images', 500);
            $table->string('thumbnails', 500);
            $table->integer('views')->default(0);
            $table->integer('count', true);
            $table->bigInteger('last_date_modified')->default(0);
            $table->bigInteger('creation_date');

            $table->unique(['id', 'code'], 'unique_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
