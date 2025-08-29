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
            ->where('available_quantity', '>', 0)
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
        $product = Product::findOrFail($id);

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

        if (isset($validated['price'])) {
            $validated['price_final'] = $validated['price'] * 1.15;
        }

        $product->update($validated);

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

    public function detailWeb($id, $clientType)
    {
        $product = Product::findOrFail($id);

        $productClone = clone $product;

        $productClone->price = $productClone->getPriceForClient($clientType);
        unset($productClone->price_final);

        $product->views += 1;
        $product->save();

        // Aplicar descuentos de campañas activas
        $productClone = $this->applyCampaignDiscounts($productClone);
        $productClone->campaign = $this->isProductInCampaign($productClone);

        return $productClone;
    }

    public function detail($id)
    {
        $product = Product::findOrFail($id);

        $product->views += 1;
        $product->save();

        // Aplicar descuentos de campañas activas
        $product = $this->applyCampaignDiscounts($product);
        $product->campaign = $this->isProductInCampaign($product);

        return $product;
    }

    /**
     * Aplica descuentos de campañas activas a un producto
     */
    public function applyCampaignDiscounts($product)
    {
        $currentDate = now();

        // Buscar campañas activas que incluyan este producto
        $activeCampaigns = Campaigns::where(function ($query) use ($currentDate) {
            $query->where(function ($q) use ($currentDate) {
                $q->where('is_active', true)
                    ->where('start_date', '<=', $currentDate)
                    ->where('end_date', '>=', $currentDate);
            })->orWhere('force_active', true);
        })
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

            $customType = $campaignProduct->pivot->custom_discount_type;
            $customValue = $campaignProduct->pivot->custom_discount_value;

            if (!is_null($customType) && !is_null($customValue)) {
                $discountType = $customType;
                $discountValue = (float) $customValue;
            } else {
                // Si alguno es null, no aplicar ningún descuento
                $discountType = null;
                $discountValue = null;
            }

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
            } else {
                $product->campaign_info = [
                    'campaign_name' => $campaign->name,
                    'campaign_slug' => $campaign->slug
                ];
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
}
