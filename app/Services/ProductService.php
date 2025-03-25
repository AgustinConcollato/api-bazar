<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class ProductService
{
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

        // Manejar transparencia para PNG
        if ($type == IMAGETYPE_PNG) {
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
                imagewebp($thumbnail, $destPath);
                break;
        }

        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }
}
