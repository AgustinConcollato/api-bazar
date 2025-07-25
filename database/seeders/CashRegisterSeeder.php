<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class CashRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cash_registers')->insert([
            'name' => 'Caja Principal',
            'id' => (string) Str::uuid(),
            'primary' => true
        ]);
    }
}
