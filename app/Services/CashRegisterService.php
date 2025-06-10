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

        // Obtener los totales de entrada y salida en una sola consulta
        $totals = CashRegister::select([
            DB::raw('SUM(CASE WHEN type = "in" THEN total_amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN type = "out" THEN total_amount ELSE 0 END) as total_out'),
            DB::raw('SUM(CASE WHEN type = "in" THEN amount ELSE 0 END) as amount_in'),
            DB::raw('SUM(CASE WHEN type = "out" THEN amount ELSE 0 END) as amount_out')
        ])->first();

        // Obtener los totales del mes actual
        $monthlyTotals = CashRegister::whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->select([
                DB::raw('SUM(CASE WHEN type = "in" THEN total_amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN type = "out" THEN total_amount ELSE 0 END) as total_out')
            ])->first();

        // Obtener los movimientos más recientes
        $movements = CashRegister::with('payment.order')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'balance' => [
                'total_in' => $totals->total_in ?? 0,
                'total_out' => $totals->total_out ?? 0,
                'available' => ($totals->amount_in ?? 0) - ($totals->amount_out ?? 0)
            ],
            'monthly' => [
                'total_in' => $monthlyTotals->total_in ?? 0,
                'total_out' => $monthlyTotals->total_out ?? 0
            ],
            'movements' => $movements
        ];
    }

    public function deposit($data)
    {
        $data['type'] = 'in';
        // En un depósito manual, amount y total_amount son iguales
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }
        return CashRegister::create($data);
    }

    public function withdraw($data)
    {
        $data['type'] = 'out';
        // En un retiro, amount y total_amount son iguales
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }
        return CashRegister::create($data);
    }
}
