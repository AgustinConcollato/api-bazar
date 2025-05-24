<?php

namespace App\Http\Controllers;

// use App\Models\Payment;
// use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Validation\ValidationException;

final class PaymentController
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function createPayment(Request $request)
    {

        try {
            $validated = $request->validate([
                'order_id' => 'required|string',
                'method' => 'required|string',
                'expected_amount' => 'required|numeric',
                'paid_amount' => 'nullable|numeric',
                'paid_at' => 'nullable|date',
            ]);

            $payment = $this->paymentService->createPayment($validated);

            return response()->json([
                'message' => 'Pago creado exitosamente',
                'payment' => $payment
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePayment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'paid_amount' => 'required|numeric',
            ]);

            $payment = $this->paymentService->updatePayment($id, $validated);

            return response()->json($payment);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
