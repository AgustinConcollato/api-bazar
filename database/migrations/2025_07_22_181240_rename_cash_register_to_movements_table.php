<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::rename('cash_registers', 'movements');
    }

    public function down()
    {
        Schema::rename('movements', 'cash_registers');
    }
};
