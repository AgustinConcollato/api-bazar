<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubcategoriesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('subcategories')->truncate();

        DB::table('subcategories')->insert([
            ['category_code' => 'CAT001', 'subcategory_name' => 'Aluminio y acero', 'subcategory_code' => 'SUBCAT001'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Melamina', 'subcategory_code' => 'SUBCAT002'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Teflón', 'subcategory_code' => 'SUBCAT003'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Cerámica', 'subcategory_code' => 'SUBCAT004'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Herméticos', 'subcategory_code' => 'SUBCAT005'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Platos y compoteras', 'subcategory_code' => 'SUBCAT006'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Vasos y copas', 'subcategory_code' => 'SUBCAT007'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Jarras, jarros y tazas', 'subcategory_code' => 'SUBCAT008'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Cubiertos', 'subcategory_code' => 'SUBCAT009'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Utensilios', 'subcategory_code' => 'SUBCAT010'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Bandejas, bowls y ensaladeras', 'subcategory_code' => 'SUBCAT011'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Botellas y bidones', 'subcategory_code' => 'SUBCAT012'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Tablas', 'subcategory_code' => 'SUBCAT013'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Artículos de asador', 'subcategory_code' => 'SUBCAT014'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Secaplatos y coladores', 'subcategory_code' => 'SUBCAT015'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Repostería', 'subcategory_code' => 'SUBCAT016'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Electrodomésticos', 'subcategory_code' => 'SUBCAT017'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Infantiles', 'subcategory_code' => 'SUBCAT018'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Rigolleau', 'subcategory_code' => 'SUBCAT019'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Carol', 'subcategory_code' => 'SUBCAT020'],
            ['category_code' => 'CAT001', 'subcategory_name' => 'Tramontina', 'subcategory_code' => 'SUBCAT021'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Decoración', 'subcategory_code' => 'SUBCAT022'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Portarretratos', 'subcategory_code' => 'SUBCAT023'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Lámparas, velas y sahumerios', 'subcategory_code' => 'SUBCAT024'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Flores y floreros', 'subcategory_code' => 'SUBCAT025'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Bolsos, billeteras y neceseres', 'subcategory_code' => 'SUBCAT026'],
            ['category_code' => 'CAT002', 'subcategory_name' => 'Relojes', 'subcategory_code' => 'SUBCAT027'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Juegos de mesa', 'subcategory_code' => 'SUBCAT028'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Didácticos', 'subcategory_code' => 'SUBCAT029'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Verano', 'subcategory_code' => 'SUBCAT030'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Vehículos', 'subcategory_code' => 'SUBCAT031'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Muñecas/os', 'subcategory_code' => 'SUBCAT032'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Animales', 'subcategory_code' => 'SUBCAT033'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Musicales', 'subcategory_code' => 'SUBCAT034'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Pelotas', 'subcategory_code' => 'SUBCAT035'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Bebé', 'subcategory_code' => 'SUBCAT036'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Nena', 'subcategory_code' => 'SUBCAT037'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Nene', 'subcategory_code' => 'SUBCAT038'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Mates', 'subcategory_code' => 'SUBCAT039'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Termos', 'subcategory_code' => 'SUBCAT040'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Equipos de mate', 'subcategory_code' => 'SUBCAT041'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Bombillas', 'subcategory_code' => 'SUBCAT042'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Vertedores', 'subcategory_code' => 'SUBCAT043'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Portatermos', 'subcategory_code' => 'SUBCAT044'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Repuestos', 'subcategory_code' => 'SUBCAT045'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Pavas', 'subcategory_code' => 'SUBCAT046'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Lumilagro', 'subcategory_code' => 'SUBCAT047'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Alfombras', 'subcategory_code' => 'SUBCAT048'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Limpieza casa', 'subcategory_code' => 'SUBCAT049'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Limpieza personal', 'subcategory_code' => 'SUBCAT050'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Baldes, fuentes y palanganas', 'subcategory_code' => 'SUBCAT051'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Ropa', 'subcategory_code' => 'SUBCAT052'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Cortinas', 'subcategory_code' => 'SUBCAT053'],
            ['category_code' => 'CAT005', 'subcategory_name' => 'Baño', 'subcategory_code' => 'SUBCAT054'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Electrónica', 'subcategory_code' => 'SUBCAT055'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Jardinería', 'subcategory_code' => 'SUBCAT056'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Librería', 'subcategory_code' => 'SUBCAT057'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Invierno', 'subcategory_code' => 'SUBCAT058'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Camping', 'subcategory_code' => 'SUBCAT059'],
            ['category_code' => 'CAT006', 'subcategory_name' => 'Organizadores', 'subcategory_code' => 'SUBCAT060'],
            ['category_code' => 'CAT003', 'subcategory_name' => 'Superhéroes', 'subcategory_code' => 'SUBCAT061'],
            ['category_code' => 'CAT004', 'subcategory_name' => 'Marwal', 'subcategory_code' => 'SUBCAT062'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Yeso', 'subcategory_code' => 'SUBCAT063'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Carameleras', 'subcategory_code' => 'SUBCAT064'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Peceras', 'subcategory_code' => 'SUBCAT065'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Frascos', 'subcategory_code' => 'SUBCAT066'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Variado en vidrio', 'subcategory_code' => 'SUBCAT067'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Hornitos', 'subcategory_code' => 'SUBCAT068'],
            ['category_code' => 'CAT007', 'subcategory_name' => 'Bombones', 'subcategory_code' => 'SUBCAT069'],
        ]);
    }
}
