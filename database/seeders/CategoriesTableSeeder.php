<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->truncate();
        
        DB::table('categories')->insert([
            ['name' => 'Cocina', 'code' => 'CAT001'],
            ['name' => 'Regalería', 'code' => 'CAT002'],
            ['name' => 'Juguetería', 'code' => 'CAT003'],
            ['name' => 'Mates y Termos', 'code' => 'CAT004'],
            ['name' => 'Limpieza y Baño', 'code' => 'CAT005'],
            ['name' => 'Varios', 'code' => 'CAT006'],
            ['name' => 'Velas', 'code' => 'CAT007'],
        ]);
    }
}
