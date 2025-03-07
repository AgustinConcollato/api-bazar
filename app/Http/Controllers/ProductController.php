<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class ProductController
{

    public function __construct(ProductService $productService, ProviderService $providerService)
    {
        $this->productService = $productService;
        $this->providerService = $providerService;
    }

    public function add(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_code' => 'required|string|max:50',
                'subcategory_code' => 'string|nullable',
                'available_quantity' => 'required|integer',
                'status' => 'required|string',
                'name' => 'required|string|max:255',
                'description' => 'string|nullable',
                'price' => 'required|numeric',
                'discount' => 'integer|nullable',
                'images' => 'required|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,webp,svg|max:2048',
                'providers' => 'string|nullable',
            ]);

            $product = $this->productService->add($request, $validated);

            $this->providerService->assignProductToProvider($validated, $product);

            return response()->json(['message' => 'Producto creado exitosamente', 'product' => $product], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error al crear el producto',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function detail($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        // $product->views += 1;
        // $product->save();

        return response()->json(array_merge(Config::get('api-responses.success.default'), ['product' => $product]));
    }
    public function search(Request $request)
    {
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $name = $request->input('name');
        $panel = $request->input('panel');
        $date = $request->input('date');
        $views = $request->input('views');

        $query = Product::query();

        if ($category) {
            $query->where('category_code', $category);
        }

        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($subcategory) {
            $query->where('subcategory_code', 'like', '%' . $subcategory . '%');
        }

        if (!$panel) {
            $query->where('status', 'active');
        }

        if ($date) {
            $query->where('creation_date', '>=', $date);

            $products = $query->orderBy('creation_date', 'desc')->paginate(10);

            return response()->json($products);
        }

        if ($views) {
            $query->get();

            $products = $query->orderBy('views', 'desc')->paginate(10);

            return response()->json($products);
        }

        $products = $query->orderBy('name')->paginate(20);

        return response()->json($products);
    }
    public function update(Request $request, $id)
    {
        try {

            $validated = $request->validate([
                'name' => 'nullable|string',
                'purchase_price' => 'nullable|numeric',
                'price' => 'nullable|numeric',
                'status' => 'nullable|string',
                'available_quantity' => 'nullable|integer',
                'category_code' => 'nullable|string',
                'subcategory_code' => 'string|nullable',
                'description' => 'string|nullable',
                'discount' => 'integer|nullable',
            ]);

            $product = $this->productService->update($validated, $id);

            return response()->json(array_merge(Config::get('api-responses.success.updated'), ['product' => $product]));

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar el producto', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el producto', 'error' => $e->getMessage()]);
        }


    }
    public function updateImages(Request $request, $id)
    {

        try {
            $validated = $request->validate([
                'index' => 'required|integer',
                'new_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $product = $this->productService->updateImages($validated, $id);

            return response()->json(['message' => 'Imagen actualizada con éxito', 'product' => $product]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar la foto', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la foto', 'errors' => $e->getMessage()]);
        }

    }
    public function addImage(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'new_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $product = $this->productService->addImage($validated, $id);

            return response()->json(['message' => 'Imagen agregada con éxito', 'product' => $product]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al agregar una nueva foto', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al agregar una nueva foto', 'error' => $e->getMessage()]);
        }
    }
    public function deleteImage(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'index' => 'required|integer'
            ]);

            $product = $this->productService->deleteImage($validated, $id);

            return response()->json(['message' => 'Imagen eliminada con éxito', 'product' => $product]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al eliminar la foto', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la foto', 'error' => $e->getMessage()]);
        }
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