<?php

namespace App\Services;

use App\Models\CashRegister;
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
        $cashRegister = CashRegister::where('primary', '=', 1)->first();

        $payment = Payment::create([
            'cash_register_id' => $cashRegister->id,
            'order_id' => $validated['order_id'],
            'method' => $validated['method'],
            'expected_amount' => $validated['expected_amount'],
            'paid_amount' => $validated['paid_amount'] ?? null,
            'paid_at' => $validated['paid_at'] ?? null,
        ]);

        // si el pago es con tarjeta, aplicar recargo al pedido
        if (
            $payment->method === 'credit_card' &&
            (!isset($validated['shopping_cart']) || !$validated['shopping_cart'])
        ) {
            $order = $payment->order;
            $surcharge = round($payment->expected_amount - ($payment->expected_amount * 100 / 110), 2);
            $order->surcharge = ($order->surcharge ?? 0) + $surcharge;
            $order->total_amount += $surcharge;
            $order->save();
        }

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

        // Suma total pagado por todos los pagos del pedido (antes de este pago)
        $totalPaidBefore = $order->payments()->where('id', '!=', $payment->id)->sum('paid_amount');
        $totalPaidNow = $totalPaidBefore + ($amount ?? $payment->paid_amount);

        // Si el método es tarjeta, quitar el recargo del pago actual
        $paidWithoutSurcharge = $payment->method === 'credit_card'
            ? ($amount ?? $payment->paid_amount) / 1.10
            : ($amount ?? $payment->paid_amount);

        $totalPaidBeforeAdjusted = $totalPaidBefore;
        // Ajustar pagos anteriores con tarjeta
        $order->payments()->where('method', 'credit_card')->where('id', '!=', $payment->id)
            ->get()
            ->each(function ($p) use (&$totalPaidBeforeAdjusted) {
                $totalPaidBeforeAdjusted -= $p->paid_amount;
                $totalPaidBeforeAdjusted += $p->paid_amount / 1.10;
            });

        $totalPaidNowAdjusted = $totalPaidBeforeAdjusted + $paidWithoutSurcharge;

        // Proporción nueva pagada con este movimiento
        $proportionBefore = $totalPaidBeforeAdjusted / $order->total_amount;
        $proportionNow = $totalPaidNowAdjusted / $order->total_amount;
        $costToRegister = round($totalCost * ($proportionNow - $proportionBefore), 2);

        $cashRegister = CashRegister::where('primary', '=', 1)->first();

        if ($costToRegister > 0) {
            $this->cashRegisterService->deposit([
                'cash_register_id' => $cashRegister->id,
                'method' => $payment->method,
                'amount' => $costToRegister,
                'total_amount' => $amount ?? $payment->paid_amount,
                'type' => 'in',
                'description' => 'Pago del pedido de ' . $order->client_name,
                'payment_id' => $payment->id
            ]);
        }
    }
}
