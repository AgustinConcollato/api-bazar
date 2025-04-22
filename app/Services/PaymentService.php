<?php

namespace App\Services;

use App\Models\Payment;

class PaymentService
{
    public function createPayment($validated)
    {
        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'method' => $validated['method'],
            'expected_amount' => $validated['expected_amount'],
            'paid_amount' => $validated['paid_amount'],
            'paid_at' => $validated['paid_at'],
        ]);

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
}