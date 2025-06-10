<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;

class PaymentService
{
    protected $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    public function createPayment($validated)
    {
        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'method' => $validated['method'],
            'expected_amount' => $validated['expected_amount'],
            'paid_amount' => $validated['paid_amount'] ?? null,
            'paid_at' => $validated['paid_at'] ?? null,
        ]);

        // Registrar en caja solo el costo proporcional al pago inicial
        if (isset($validated['paid_amount']) && isset($validated['paid_at']) && $validated['paid_amount'] > 0) {
            $this->createCashRegisterEntry($payment, $validated['paid_amount']);
        }

        return $payment;
    }

    public function getPaymentDetails($paymentId)
    {
        return Payment::find($paymentId);
    }

    public function getPaymentsByOrder($orderId)
    {
        return Payment::where('order_id', $orderId)->get();
    }

    public function updatePayment($id, $data)
    {
        $payment = Payment::find($id);
        $previousPaidAmount = $payment->paid_amount ?? 0;

        $data['paid_at'] = Carbon::now();
        $payment->update($data);

        // Registrar en caja solo la diferencia pagada
        if (isset($data['paid_amount'])) {
            $amountToRegister = $data['paid_amount'] - $previousPaidAmount;
            if ($amountToRegister > 0) {
                $this->createCashRegisterEntry($payment, $amountToRegister);
            }
        }

        return $payment;
    }

    private function createCashRegisterEntry($payment, $amount = null)
    {
        $order = $payment->order;
        $orderProducts = $order->products;
        $totalCost = $orderProducts->sum(function ($product) {
            return $product->purchase_price * $product->quantity;
        });
        $expectedAmount = $payment->expected_amount;
        $paidAmount = $amount ?? $expectedAmount;

        // Calcular el total pagado hasta ahora (incluyendo este movimiento)
        $totalPaid = ($payment->paid_amount ?? 0);
        $previousPaid = $totalPaid - $paidAmount;
        // Si la suma supera el total esperado, solo registrar hasta el total esperado
        $maxToConsider = max(0, $expectedAmount - $previousPaid);
        $paidForCost = min($paidAmount, $maxToConsider);
        $proportion = $expectedAmount > 0 ? ($paidForCost / $expectedAmount) : 0;
        $costToRegister = round($totalCost * $proportion, 2);

        if ($costToRegister > 0) {
            $this->cashRegisterService->deposit([
                'method' => $payment->method,
                'amount' => $costToRegister,
                'total_amount' => $paidAmount,
                'type' => 'in',
                'description' => 'Pago del pedido de ' . $order->client_name,
                'payment_id' => $payment->id
            ]);
        }
    }
}   
