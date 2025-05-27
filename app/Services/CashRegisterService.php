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
            DB::raw('SUM(CASE WHEN type = "in" THEN amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN type = "out" THEN amount ELSE 0 END) as total_out')
        ])->first();

        // Obtener los totales del mes actual
        $monthlyTotals = CashRegister::whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->select([
                DB::raw('SUM(CASE WHEN type = "in" THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN type = "out" THEN amount ELSE 0 END) as total_out')
            ])->first();

        // Obtener los movimientos más recientes
        $movements = CashRegister::with('payment.order')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return [$totals, $monthlyTotals, $movements];
    }

    public function deposit($data)
    {
        $data['type'] ??= 'in';
        return CashRegister::create($data);
    }

    public function withdraw($data)
    {
        $data['type'] ??= 'out';
        return CashRegister::create($data);
    }
}
