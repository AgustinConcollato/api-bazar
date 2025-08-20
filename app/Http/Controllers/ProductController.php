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

    protected $productService;
    protected $providerService;

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
                'price_final' => 'nullable|numeric|min:0',
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

    public function detailWeb(Request $request, $id)
    {
        $client = $request->user('client');
        $clientType = $client ? $client->type : 'final';

        $product = $this->productService->detailWeb($id, $clientType);

        return response()->json(['product' => $product, 'client'=> $client]);
    }

    public function detail(Request $request, $id)
    {
        $product = $this->productService->detail($id, $request);

        $providers = $this->providerService->getProvidersByProduct($id);

        $product['providers'] = $providers;

        return response()->json(array_merge(Config::get('api-responses.success.default'), ['product' => $product]));
    }

    /**
     * Construye la query base con filtros comunes
     */
    private function buildBaseQuery(Request $request, $query)
    {
        // Filtros básicos
        if ($request->input('category')) {
            $query->where('category_code', $request->input('category'));
        }

        if ($request->input('subcategory')) {
            $query->where('subcategory_code', 'like', '%' . $request->input('subcategory') . '%');
        }

        if ($request->input('name')) {
            $this->applyNameFilter($query, $request->input('name'));
        }

        // Filtros adicionales
        if ($request->input('available_quantity')) {
            $query->where('available_quantity', '>', 0);
        }

        if ($request->input('discount')) {
            $query->where('discount', '>', 0);
        }

        return $query;
    }

    /**
     * Aplica filtro de búsqueda por nombre
     */
    private function applyNameFilter($query, $name)
    {
        $query->where(function ($q) use ($name) {
            $q->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", ['"' . $name . '"'])
                ->orWhere(function ($q) use ($name) {
                    $keywords = explode(' ', $name);
                    foreach ($keywords as $word) {
                        $q->where('name', 'like', '%' . $word . '%');
                    }
                });
        });
    }

    /**
     * Aplica ordenamiento a la query
     */
    private function applyOrdering($query, Request $request)
    {
        if ($request->input('views')) {
            $query->orderBy('views', 'desc');
        } elseif ($request->input('price') === 'min') {
            $query->orderBy('price', 'asc');
        } elseif ($request->input('price') === 'max') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('name');
        }
    }

    /**
     * Aplica información de campañas a los productos
     */
    private function applyCampaignInfo($products)
    {
        foreach ($products as $product) {
            $campaignInfo = $this->productService->isProductInCampaign($product);

            // Si tiene providers cargados, es panel (admin)
            if ($product->relationLoaded('providers')) {
                $product->in_campaign = $campaignInfo["in_campaign"];
            }

            $this->productService->applyCampaignDiscounts($product);
        }
    }

    /**
     * Búsqueda específica para productos recientes
     */
    public function searchRecent(Request $request)
    {
        $client = $request->user('client');

        $query = Product::query();

        // Construir query base
        $query = $this->buildBaseQuery($request, $query);

        // Solo productos activos para web
        $query->where('status', 'active');

        $products = $query->where('created_at', '>=', now()->subDays(10))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Aplicar descuentos de campañas activas
        $products->getCollection()->transform(function ($product) {
            return $this->productService->applyCampaignDiscounts($product);
        });

        $clientType = $client ? $client->type : 'final';

        $products->transform(function ($product) use ($clientType) {
            $product->price = $product->getPriceForClient($clientType);
            unset($product->price_final); // opcional: ocultar del JSON
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Búsqueda específica para el panel de control
     */
    public function searchPanel(Request $request)
    {
        $query = Product::with('providers');

        // Filtros de estado
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        // Construir query base
        $query = $this->buildBaseQuery($request, $query);

        // Aplicar ordenamiento
        $this->applyOrdering($query, $request);

        $products = $query->paginate(20);

        // Aplicar información de campañas
        $this->applyCampaignInfo($products);

        return response()->json($products);
    }

    /**
     * Búsqueda específica para la web (clientes)
     */
    public function searchWeb(Request $request)
    {
        $client = $request->user('client');

        $query = Product::query();

        // Construir query base
        $query = $this->buildBaseQuery($request, $query);

        // Solo productos activos para web
        $query->where('status', 'active');

        // Aplicar ordenamiento
        $this->applyOrdering($query, $request);

        $products = $query->paginate(20);

        // Aplicar descuentos de campañas activas
        $this->applyCampaignInfo($products);

        $clientType = $client ? $client->type : 'final';

        $products->getCollection()->transform(function ($product) use ($clientType) {
            $product->price = $product->getPriceForClient($clientType);
            unset($product->price_final); // opcional: ocultar del JSON
            return $product;
        });

        return response()->json($products);
    }

    public function relatedProducts(Request $request, $productId)
    {
        try {

            $client = $request->user('client');

            $products = $this->productService->relatedProducts($productId);

            $clientType = $client ? $client->type : 'final';

            $products->transform(function ($product) use ($clientType) {
                $product->price = $product->getPriceForClient($clientType);
                unset($product->price_final); // opcional: ocultar del JSON
                return $product;
            });

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener productos relacionados', 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $validated = $request->validate([
                'name' => 'nullable|string',
                'purchase_price' => 'nullable|numeric',
                'price' => 'nullable|numeric',
                'price_final' => 'nullable|numeric|min:0',
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
