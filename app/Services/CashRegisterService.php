<?php

namespace App\Services;

use App\Models\CashRegister;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function get()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Obtener el último movimiento para el saldo actual
        $last = CashRegister::orderBy('created_at', 'desc')->first();
        $current_balance = $last ? $last->current_balance : 0;

        // Obtener los totales del mes actual
        $monthlyTotals = CashRegister::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select([
                DB::raw('SUM(CASE WHEN type = "in" THEN total_amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN type = "out" THEN total_amount ELSE 0 END) as total_out'),
                DB::raw('SUM(CASE WHEN type = "in" THEN amount ELSE 0 END) as amount_in'),
                DB::raw('SUM(CASE WHEN type = "out" THEN amount ELSE 0 END) as amount_out')
            ])->first();

        // Obtener los movimientos más recientes
        $movements = CashRegister::with('payment.order')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'balance' => [
                'available' => $current_balance
            ],
            'monthly' => [
                'total_in' => $monthlyTotals->total_in ?? 0,
                'total_out' => $monthlyTotals->total_out ?? 0,
                'amount_in' => $monthlyTotals->amount_in ?? 0,
                'amount_out' => $monthlyTotals->amount_out ?? 0
            ],
            'movements' => $movements
        ];
    }

    public function deposit($data)
    {
        $data['type'] = 'in';
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }
        $last = CashRegister::orderBy('created_at', 'desc')->first();
        $previous_balance = $last ? $last->current_balance : 0;
        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance + $data['amount'];
        return CashRegister::create($data);
    }

    public function withdraw($data)
    {
        $data['type'] = 'out';
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }
        $last = CashRegister::orderBy('created_at', 'desc')->first();
        $previous_balance = $last ? $last->current_balance : 0;
        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance - $data['amount'];
        return CashRegister::create($data);
    }
}
