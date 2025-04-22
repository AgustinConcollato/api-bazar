<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AnalyticsController
{

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
    public function netProfit(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional
            ]);

            $netProfit = $this->analyticsService->netProfit($validated);

            if ($netProfit === null) {
                return response()->json([
                    'error' => 'No se encontraron pedidos para el año y mes especificados',
                ], 404);
            }

            return response()->json([
                'net_profit' => $netProfit,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el beneficio neto',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
