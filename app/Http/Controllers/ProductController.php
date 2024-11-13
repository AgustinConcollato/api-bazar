<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

function createThumbnail($sourcePath, $destPath, $maxWidth, $maxHeight)
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
class ProductController
{
    public function add(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|string|max:50',
            'subcategory' => 'string|nullable',
            'available_quantity' => 'integer|nullable',
            'status' => 'required|string',
            'creation_date' => 'required|integer',
            'last_date_modified' => 'integer|nullable',
            'name' => 'required|string|max:255',
            'description' => 'string|nullable',
            'code' => 'required|string',
            'id' => 'required|string',
            'price' => 'required|numeric',
            'discount' => 'integer|nullable',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,svg|max:2048'
        ]);

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

                createThumbnail($image->getRealPath(), $thumbnailFullPath, 100, 100);

                $thumbnailPaths[] = $thumbnailPath;
            }
        }

        $validatedData['images'] = json_encode($imagePaths);
        $validatedData['thumbnails'] = json_encode($thumbnailPaths);

        $product = Product::create($validatedData);
        return response()->json(['message' => 'Producto creado exitosamente', 'product' => $product], 201);

    }
    public function detail($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        return response()->json(array_merge(Config::get('api-responses.success.default'), ['product' => $product]));
    }
    public function search(Request $request)
    {
        $category = $request->input('category');
        $name = $request->input('name');

        $query = Product::query();

        if ($name) {
            if ($category) {
                $query->where('category_id', $category);
            }

            $query->where('name', 'like', '%' . $name . '%');
        } else {
            $query->where('category_id', $category);
        }

        $products = $query->orderBy('name')->paginate(20);

        return response()->json($products);
    }
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        $validatedData = $request->validate([
            'name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'status' => 'nullable|string',
            'available_quantity' => 'nullable|integer',
            'last_date_modified' => 'required|integer',
            'category_id' => 'nullable|string',
            'subcategory' => 'string|nullable',
            'description' => 'string|nullable',
            'discount' => 'integer|nullable',
        ]);

        $product->update($validatedData);

        return response()->json(array_merge(Config::get('api-responses.success.updated'), ['product' => $product]));
    }
    public function updateImages(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        $validatedData = $request->validate([
            'index' => 'required|integer', // Índice de la imagen a reemplazar
            'new_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Decodifica las imágenes almacenadas como JSON
        $images = json_decode($product->images, true);
        $thumbnails = json_decode($product->thumbnails, true);

        // Verifica si el índice es válido
        if (!isset($images[$validatedData['index']])) {
            return response()->json(['error' => 'El índice de la imagen no existe'], 400);
        }

        // Almacena la nueva imagen
        $newImagePath = $validatedData['new_image']->store('images/products', 'public');
        $images[$validatedData['index']] = $newImagePath;

        // Crea una miniatura de la nueva imagen
        $newThumbnailPath = 'images/min/products/' . basename($newImagePath);
        createThumbnail($validatedData['new_image']->getRealPath(), public_path('storage/' . $newThumbnailPath), 150, 150);
        $thumbnails[$validatedData['index']] = $newThumbnailPath;

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode($images);
        $product->thumbnails = json_encode($thumbnails);
        $product->save();

        return response()->json(['message' => 'Imagen actualizada con éxito', 'product' => $product]);
    }
    public function delete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        $images = json_decode($product->images);
        $thumbnails = json_decode($product->thumbnails);

        if ($images) {
            foreach ($images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        if ($thumbnails) {
            foreach ($thumbnails as $thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        $product->delete();

        return response()->json(Config::get('api-responses.success.deleted'));
    }
}