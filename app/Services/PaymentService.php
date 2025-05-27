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

        // Create cash register entry if payment is paid
        if (isset($validated['paid_amount']) && isset($validated['paid_at'])) {
            $this->createCashRegisterEntry($payment);
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
        $data['paid_at'] = Carbon::now();
        $payment = Payment::find($id);
        $payment->update($data);

        // Create cash register entry if payment is being marked as paid
        if (isset($data['paid_amount'])) {
            $this->createCashRegisterEntry($payment);
        }

        return $payment;
    }

    private function createCashRegisterEntry($payment)
    {
        // Obtener el pedido y sus productos
        $order = $payment->order;
        $orderProducts = $order->products;

        // Calcular el costo total basado en purchase_price
        $totalCost = $orderProducts->sum(function ($product) {
            return $product->purchase_price * $product->quantity;
        });

        $this->cashRegisterService->deposit([
            'method' => $payment->method,
            'amount' => $totalCost,
            'type' => 'in',
            'description' => 'Pago del pedido de ' . $order->client_name,
            'payment_id' => $payment->id
        ]);
    }
}
