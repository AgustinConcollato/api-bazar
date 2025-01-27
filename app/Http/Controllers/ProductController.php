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
            'code' => 'string|nullable',
            'id' => 'required|string',
            'price' => 'required|numeric',
            'discount' => 'integer|nullable',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,svg|max:2048'
        ]);

        $categoryId = $validatedData['category_id'];

        $prefix = substr($categoryId, -3);

        $latestProduct = Product::where('category_id', $categoryId)
            ->orderBy('code', 'desc')
            ->first();

        if ($latestProduct) {
            $latestCodeNumber = (int) substr($latestProduct->code, 3);
            $newCodeNumber = $latestCodeNumber + 1;
        } else {
            $newCodeNumber = 1;
        }

        $newCode = substr($prefix, -2) . str_pad($newCodeNumber, 3, '0', STR_PAD_LEFT);
        $validatedData['code'] = $newCode;

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
        $subcategory = $request->input('subcategory');
        $name = $request->input('name');
        $panel = $request->input('panel');
        $date = $request->input('date');

        $query = Product::query();

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($subcategory) {
            $query->where('subcategory', 'like', '%' . $subcategory . '%');
        }

        if (!$panel) {
            $query->where('status', 'active');
        }

        if ($date) {
            $query->where('creation_date', '>=', $date);
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
            'index' => 'required|integer',
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
    public function addImage(Request $request, $id)
    {
        $validatedData = $request->validate([
            'new_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

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
        $newImagePath = $validatedData['new_image']->store('images/products', 'public');
        $images[] = $newImagePath;

        // Crea una miniatura de la nueva imagen
        $newThumbnailPath = 'images/min/products/' . basename($newImagePath);
        createThumbnail($validatedData['new_image']->getRealPath(), public_path('storage/' . $newThumbnailPath), 150, 150);
        $thumbnails[] = $newThumbnailPath;

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode($images);
        $product->thumbnails = json_encode($thumbnails);
        $product->save();

        return response()->json(['message' => 'Imagen agregada con éxito', 'product' => $product]);
    }
    public function deleteImage(Request $request, $id)
    {
        $validatedData = $request->validate([
            'index' => 'required|integer'
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        // Decodifica las imágenes almacenadas como JSON
        $images = json_decode($product->images, true);
        $thumbnails = json_decode($product->thumbnails, true);

        // Verifica si el índice es válido
        if (!isset($images[$validatedData['index']])) {
            return response()->json(['error' => 'El índice de la imagen no existe'], 400);
        }

        // tengo que eliminar la imagen que se encuentra en el índice
        $imagePath = $images[$validatedData['index']];
        $thumbnailPath = $thumbnails[$validatedData['index']];
        Storage::disk('public')->delete($imagePath);
        Storage::disk('public')->delete($thumbnailPath);

        // Elimina la imagen y la miniatura del arreglo
        unset($images[$validatedData['index']]);
        unset($thumbnails[$validatedData['index']]);

        // Actualiza el producto con las nuevas rutas de imagen y miniatura
        $product->images = json_encode(array_values($images));
        $product->thumbnails = json_encode(array_values($thumbnails));
        $product->save();

        return response()->json(['message' => 'Imagen eliminada con éxito', 'product' => $product]);
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