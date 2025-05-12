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

    public function grossProfit(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional   
            ]);

            $grossProfit = $this->analyticsService->grossProfit($validated);

            return response()->json([
                'gross_profit' => $grossProfit,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el beneficio bruto',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function profitPercentage(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional
            ]);

            $profitPercentage = $this->analyticsService->profitPercentage($validated);

            return response()->json([
                'profit_percentage' => $profitPercentage,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el porcentaje de beneficio',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cost(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional
            ]);

            $cost = $this->analyticsService->cost($validated);

            return response()->json([
                'cost' => $cost,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el costo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function resume(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional
            ]);

            $resume = $this->analyticsService->resume($validated);

            return response()->json([
                'resume' => $resume,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el resumen',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function compareWithPreviousMonth(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'required|integer|min:1|max:12', // El mes es requerido para la comparación
            ]);

            $comparison = $this->analyticsService->compareWithPreviousMonth($validated);

            return response()->json($comparison);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al comparar con el mes anterior',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function compareWithPreviousYear(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12', // El mes es opcional para la comparación anual
            ]);

            $comparison = $this->analyticsService->compareWithPreviousYear($validated);

            return response()->json([
                'comparison' => $comparison,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al comparar con el año anterior',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
