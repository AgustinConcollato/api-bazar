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
        Schema::create('products', function (Blueprint $table) {
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->string('category_code', 80);
            $table->string('subcategory_code', 80)->nullable();
            $table->integer('available_quantity');
            $table->string('code', 25)->unique();
            $table->uuid('id')->primary();
            $table->decimal('price', 11, 2);
            $table->integer('discount')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('images', 500);
            $table->string('thumbnails', 500);
            $table->integer('views')->default(0);
            $table->timestamps();

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
