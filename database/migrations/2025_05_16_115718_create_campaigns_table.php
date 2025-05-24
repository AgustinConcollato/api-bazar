<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(false);
            $table->string('image', 500);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
