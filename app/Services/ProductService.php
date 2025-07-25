<?php

namespace App\Services;

use App\Models\Campaigns;
use App\Models\Categories;
use App\Models\Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Services\ProviderService;

class ProductService
{

    protected $providerService;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    public function add($request, $validated)
    {

        $categoryCode = $validated['category_code'];

        // Obtener el código de la categoría desde la tabla categories
        $category = Categories::where('code', $categoryCode)->first();

        if (!$category) {
            throw new \Exception("Categoría no encontrada");
        }

        // Extraer el número del código de la categoría (últimos dígitos)
        $categoryNumber = (int) substr($category->code, -3);

        // Obtener el producto con el código más alto en la misma categoría
        $latestProduct = Product::where('category_code', $categoryCode)
            ->orderBy('code', 'desc')
            ->first();

        if ($latestProduct) {
            // Extraer la parte AAA del código (los últimos 3 dígitos)
            $latestCodeNumber = (int) substr($latestProduct->code, -3);
            $newProductNumber = $latestCodeNumber + 1;
        } else {
            // Si no hay productos en la categoría, empezar en 001
            $newProductNumber = 1;
        }

        // Construir el código del producto (ejemplo: 012001)
        $newCode = "0" . $categoryNumber . str_pad($newProductNumber, 3, '0', STR_PAD_LEFT);

        $validated['code'] = $newCode;

        $imagePaths = [];
        $thumbnailPaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('images/products', 'public');
                $imagePaths[] = $path;

                $thumbnailPath = 'images/min/products/' . basename($path);

                $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);

                if (!file_exists(dirname($thumbnailFullPath))) {
                    mkdir(dirname($thumbnailFullPath), 0755, true);
                }

                $this->createThumbnail($image->getRealPath(), $thumbnailFullPath, 100, 100);

                $thumbnailPaths[] = $thumbnailPath;
            }
        }

        $validated['images'] = json_encode($imagePaths);
        $validated['thumbnails'] = json_encode($thumbnailPaths);

        $product = Product::create($validated);

        return $product;
    }

    public function relatedProducts($productId)
    {
        $product = Product::findOrFail($productId);

        // Obtener subcategorías como array
        $subcategories = explode('|', $product->subcategory_code);

        // Dividir el nombre en palabras clave
        $keywords = collect(explode(' ', $product->name))
            ->filter(fn($word) => strlen($word) > 3); // ignorar palabras cortas

        $query = Product::where('id', '!=', $product->id)
            ->where('status', 'active')
            ->where('category_code', $product->category_code)
            // ->where(function ($q) use ($subcategories) {
            //     foreach ($subcategories as $subcat) {
            //         $q->orWhere('subcategory_code', 'like', '%' . $subcat . '%');
            //     }
            // })
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', '%' . $word . '%');
                }
            })
            ->orderBy('views', 'desc') // o por created_at si preferís
            ->limit(10)
            ->get();

        // Aplicar descuentos de campañas activas a los productos relacionados
        $query->transform(function ($product) {
            return $this->applyCampaignDiscounts($product);
        });

        return $query;
    }

    public function updateImages($validated, $id)
    {

        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        $images = json_decode($product->images, true);
        $thumbnails = json_decode($product->thumbnails, true);

        // Verifica si el índice es válido
        if (!isset($images[$validated['index']])) {
            return response()->json(['error' => 'El índice de la imagen no existe'], 400);
        }

        // Elimina las imágenes anteriores si existen
        $oldImagePath = $images[$validated['index']];
        $oldThumbnailPath = $thumbnails[$validated['index']] ?? null;

        if (Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        if ($oldThumbnailPath && Storage::disk('public')->exists($oldThumbnailPath)) {
            Storage::disk('public')->delete($oldThumbnailPath);
        }

        // Almacena la nueva imagen
        $newImagePath = $validated['new_image']->store('images/products', 'public');
        $images[$validated['index']] = $newImagePath;

        // Crea una miniatura de la nueva imagen
        $newThumbnailPath = 'images/min/products/' . basename($newImagePath);
        $this->createThumbnail($validated['new_image']->getRealPath(), public_path('storage/' . $newThumbnailPath), 150, 150);
        $thumbnails[$validated['index']] = $newThumbnailPath;

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode($images);
        $product->thumbnails = json_encode($thumbnails);
        $product->save();

        return $product;
    }

    public function addImage($validated, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        // Decodifica las imágenes almacenadas como JSON
        $images = json_decode($product->images, true);
        $thumbnails = json_decode($product->thumbnails, true);

        if (count($images) >= 5) {
            return response()->json(['error' => 'No se pueden agregar más de 5 imágenes'], 400);
        }

        // Almacena la nueva imagen
        $newImagePath = $validated['new_image']->store('images/products', 'public');
        $images[] = $newImagePath;

        // Crea una miniatura de la nueva imagen
        $newThumbnailPath = 'images/min/products/' . basename($newImagePath);
        $this->createThumbnail($validated['new_image']->getRealPath(), public_path('storage/' . $newThumbnailPath), 150, 150);
        $thumbnails[] = $newThumbnailPath;

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode($images);
        $product->thumbnails = json_encode($thumbnails);
        $product->save();

        return $product;
    }

    public function deleteImage($validated, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            throw new \Exception(Config::get('api-responses.error.not_found'));
        }

        // Decodifica las imágenes almacenadas como JSON
        $images = json_decode($product->images, true);
        $thumbnails = json_decode($product->thumbnails, true);

        // Verifica si el índice es válido
        if (!isset($images[$validated['index']])) {
            throw new \Exception('El índice de la imagen no existe');
        }

        // tengo que eliminar la imagen que se encuentra en el índice
        $imagePath = $images[$validated['index']];
        $thumbnailPath = $thumbnails[$validated['index']];
        Storage::disk('public')->delete($imagePath);
        Storage::disk('public')->delete($thumbnailPath);

        // Elimina la imagen y la miniatura del arreglo
        unset($images[$validated['index']]);
        unset($thumbnails[$validated['index']]);

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode(array_values($images));
        $product->thumbnails = json_encode(array_values($thumbnails));
        $product->save();

        return $product;
    }

    public function update($validated, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            throw new \Exception(Config::get('api-responses.error.not_found'));
        }

        if (!empty($validated['category_code']) && $validated['category_code'] !== $product->category_code) {
            $newCategoryCode = $validated['category_code'];

            $category = Categories::where('code', $newCategoryCode)->first();
            if (!$category) {
                throw new \Exception("Categoría no encontrada");
            }

            $categoryNumber = (int) substr($category->code, -3);

            // Obtener el producto con el código más alto en la misma categoría
            $latestProduct = Product::where('category_code', $newCategoryCode)
                ->orderBy('code', 'desc')
                ->first();


            if ($latestProduct) {
                // Extraer la parte AAA del código (los últimos 3 dígitos)
                $latestCodeNumber = (int) substr($latestProduct->code, -3);
                $newProductNumber = $latestCodeNumber + 1;
            } else {
                // Si no hay productos en la categoría, empezar en 001
                $newProductNumber = 1;
            }

            // Construir el código del producto (ejemplo: 012001)
            $newCode = "0" . $categoryNumber . str_pad($newProductNumber, 3, '0', STR_PAD_LEFT);

            $validated['code'] = $newCode;
            $validated['subcategory_code'] = null;
        }

        $product->update($validated);

        $product['sales_velocity'] = $this->calculateSalesVelocity($product);

        return $product;
    }

    public function createThumbnail($sourcePath, $destPath, $maxWidth, $maxHeight)
    {
        // Cargar la imagen
        list($sourceWidth, $sourceHeight, $type) = getimagesize($sourcePath);

        // Crear la imagen según el tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                break;
        }

        $aspectRatio = $sourceWidth / $sourceHeight;
        if ($maxWidth / $maxHeight > $aspectRatio) {
            $newWidth = (int) ($maxHeight * $aspectRatio);
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = (int) ($maxWidth / $aspectRatio);
        }

        // Crear una imagen en blanco para la miniatura
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Manejar transparencia para PNG y WebP
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
            // Habilitar la mezcla alfa y configurar el fondo transparente
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparentColor = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparentColor);
        }

        // Copiar y redimensionar la imagen original en la miniatura
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        // guardar segun el tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destPath);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumbnail, $destPath, 100); // Añadido calidad 100 para mejor preservación de transparencia
                break;
        }

        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    public function detail($id, $request)
    {
        $product = Product::find($id);

        if (!$product) {
            throw new \Exception(Config::get('api-responses.error.not_found'));
        }

        $panel = $request->input('panel', false);

        if ($panel) {
            $providers = $this->providerService->getProvidersByProduct($id);

            $product['providers'] = $providers;
        } else {
            $product->views += 1;
            $product->save();
        }

        // Aplicar descuentos de campañas activas
        $product = $this->applyCampaignDiscounts($product);

        return $product;
    }

    /**
     * Aplica descuentos de campañas activas a un producto
     */
    public function applyCampaignDiscounts($product)
    {
        $currentDate = now();

        // Buscar campañas activas que incluyan este producto
        $activeCampaigns = Campaigns::where('is_active', true)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->whereHas('products', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->with(['products' => function ($query) use ($product) {
                $query->where('product_id', $product->id);
            }])
            ->get();

        if ($activeCampaigns->isNotEmpty()) {
            // Tomar la primera campaña activa (o puedes implementar lógica para priorizar)
            $campaign = $activeCampaigns->first();
            $campaignProduct = $campaign->products->first();

            // Aplicar descuento personalizado del producto en la campaña o el descuento general de la campaña
            $discountType = $campaignProduct->pivot->custom_discount_type ?? $campaign->discount_type;
            $discountValue = (int)($campaignProduct->pivot->custom_discount_value ?? $campaign->discount_value);

            if ($discountType && $discountValue) {

                $product->campaign_discount = [
                    'type' => $discountType,
                    'value' => $discountValue,
                    'campaign_name' => $campaign->name,
                    'campaign_slug' => $campaign->slug
                ];

                // Calcular precio con descuento
                if ($discountType === 'percentage') {
                    $product->final_price = $product->price - ($product->price * ($discountValue / 100));
                } else {
                    $product->final_price = max(0, $product->price - $discountValue);
                }
            }
        }

        return $product;
    }

    /**
     * Aplica descuentos de campañas activas a una colección de productos
     */
    public function isProductInCampaign($product)
    {
        // Buscar si el producto está en cualquier campaña (activa o inactiva)
        $campaign = Campaigns::whereHas('products', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->first();

        if ($campaign) {
            return [
                'in_campaign' => true,
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'slug' => $campaign->slug,
                    'is_active' => $campaign->is_active,
                    'start_date' => $campaign->start_date,
                    'end_date' => $campaign->end_date
                ]
            ];
        }

        return [
            'in_campaign' => false,
            'campaign' => null
        ];
    }

    // /**
    //  * Determina el estado de una campaña
    //  */
    // private function getCampaignStatus($campaign)
    // {
    //     $currentDate = now();

    //     if (!$campaign->is_active) {
    //         return 'inactive';
    //     }

    //     if ($currentDate < $campaign->start_date) {
    //         return 'not_started';
    //     }

    //     if ($currentDate > $campaign->end_date) {
    //         return 'expired';
    //     }

    //     return 'active';
    // }

    public function calculateSalesVelocity($product)
    {
        // Usar la relación eager loaded para calcular las ventas
        $recentSales = $product->orderProducts ?? collect();

        // Filtrar solo las ventas completadas
        $recentSales = $recentSales->filter(function ($orderProduct) {
            return $orderProduct->order->status === 'completed';
        });

        $lastWeekSales = $recentSales->filter(function ($orderProduct) {
            return $orderProduct->order->updated_at >= now()->subDays(7);
        });

        $totalQuantitySold = $recentSales->sum('quantity');
        $lastWeekQuantitySold = $lastWeekSales->sum('quantity');
        $weeksAnalyzed = 4; // 30 días ≈ 4 semanas
        $velocityPerWeek = $totalQuantitySold / $weeksAnalyzed;

        // Calcular semanas estimadas para agotar stock actual
        $weeksUntilStockout = $product->available_quantity > 0 && $velocityPerWeek > 0
            ? ceil($product->available_quantity / $velocityPerWeek)
            : null;

        return [
            'total_sold_last_30_days' => $totalQuantitySold, // ventas de los últimos 30 días
            'total_sold_last_week' => $lastWeekQuantitySold, // ventas de la última semana
            'velocity_per_week' => round($velocityPerWeek, 2), // velocidad de ventas por semana
            'weeks_until_stockout' => $weeksUntilStockout, // semanas para agotar stock
            'status' => $this->getStockStatus($weeksUntilStockout, $velocityPerWeek)
        ];
    }

    private function getStockStatus($weeksUntilStockout, $velocityPerWeek)
    {
        if ($weeksUntilStockout === null) {
            return 'sin_ventas';
        }

        if ($weeksUntilStockout <= 1) { // 1 semana o menos
            return 'stock_critico';
        } elseif ($weeksUntilStockout <= 2) { // 1-2 semanas
            return 'stock_bajo';
        } elseif ($weeksUntilStockout <= 4) { // 2-4 semanas
            return 'stock_medio';
        } else { // más de 4 semanas
            return 'stock_suficiente';
        }
    }

    public function getProductsBySalesVelocity()
    {
        // Obtener productos activos con solo los campos necesarios de sus ventas
        $products = Product::where('status', 'active')
            ->with(['orderProducts' => function ($query) {
                $query->select('product_id', 'quantity', 'order_id')
                    ->whereHas('order', function ($q) {
                        $q->where('updated_at', '>=', now()->subDays(30));
                    })
                    ->with(['order' => function ($q) {
                        $q->select('id', 'client_id', 'client_name', 'updated_at', 'status');
                    }]);
            }])
            ->select([
                'id',
                'name',
                'available_quantity',
                'price',
                'thumbnails',
                'discount'
            ])
            ->get();

        $productsWithVelocity = [];

        foreach ($products as $product) {
            $velocity = $this->calculateSalesVelocity($product);

            // Solo incluir productos que tienen ventas
            if ($velocity['total_sold_last_30_days'] > 2) {
                // Transformar order_products en orders con la información combinada
                if ($product->orderProducts) {
                    $product->orders = $product->orderProducts->map(function ($orderProduct) use ($product) {
                        return [
                            'quantity' => $orderProduct->quantity,
                            'id' => $orderProduct->order_id,
                            'client_id' => $orderProduct->order->client_id,
                            'client_name' => $orderProduct->order->client_name,
                            'updated_at' => $orderProduct->order->updated_at
                        ];
                    });
                } else {
                    $product->orders = [];
                }

                unset($product->orderProducts);

                $product->sales_velocity = $velocity;
                $product->priority_score = $this->calculatePriorityScore($velocity, $product);
                $productsWithVelocity[] = $product;
            }
        }

        // Ordenar por prioridad (primero los que necesitan reposición más urgente)
        usort($productsWithVelocity, function ($a, $b) {
            return $b->priority_score <=> $a->priority_score;
        });

        // Aplicar descuentos de campañas activas
        foreach ($productsWithVelocity as $product) {
            $this->applyCampaignDiscounts($product);
        }

        // Limitar a los 20 productos más prioritarios
        return array_slice($productsWithVelocity, 0, 20);
    }

    private function calculatePriorityScore($velocity, $product)
    {
        $score = 0;

        // Prioridad por estado de stock
        switch ($velocity['status']) {
            case 'stock_critico':
                $score += 100;
                break;
            case 'stock_bajo':
                $score += 75;
                break;
            case 'stock_medio':
                $score += 50;
                break;
            case 'stock_suficiente':
                $score += 25;
                break;
        }

        // Prioridad por velocidad de venta (más ventas = más prioridad)
        $score += min($velocity['velocity_per_week'] * 10, 50);

        // Prioridad por ratio de stock/ventas semanales
        if ($velocity['velocity_per_week'] > 0) {
            $stockToSalesRatio = $product->available_quantity / $velocity['velocity_per_week'];
            if ($stockToSalesRatio < 1) {
                $score += 50; // Stock menor a una semana de ventas
            } elseif ($stockToSalesRatio < 2) {
                $score += 25; // Stock menor a dos semanas de ventas
            }
        }

        return $score;
    }
}
