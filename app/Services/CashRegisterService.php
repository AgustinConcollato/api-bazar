<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\CashRegister;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{

    public function create($data)
    {
        $amount = 0;
        if ($data['balance'] > 0) {
            $amount = $data['balance'];
            $data['balance'] = 0;
        }

        $newCashRegister = CashRegister::create($data);

        if ($amount > 0) {
            $this->deposit(
                [
                    'cash_register_id' => $newCashRegister['id'],
                    'amount' => $amount,
                    'total_amount' => $amount,
                    'method' => 'transfer'
                ]
            );
        }

        return $newCashRegister;
    }

    public function get($id = null)
    {
        if ($id) {

            $cashRegister = CashRegister::find($id);

            $movements = $cashRegister->movements()
                ->with('payment.order')
                ->paginate(50);

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // // Obtener los totales del mes actual
            $monthlyTotals = Movement::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereIn('cash_register_id', [$cashRegister['id']])
                ->select([
                    DB::raw('SUM(CASE WHEN type = "in" THEN total_amount ELSE 0 END) as total_in'),
                    DB::raw('SUM(CASE WHEN type = "out" THEN total_amount ELSE 0 END) as total_out'),
                    DB::raw('SUM(CASE WHEN type = "in" THEN amount ELSE 0 END) as amount_in'),
                    DB::raw('SUM(CASE WHEN type = "out" THEN amount ELSE 0 END) as amount_out')
                ])->first();

            return [
                'balance' => $cashRegister['balance'],
                'movements' => $movements,
                'monthly' => [
                    'total_in' => $monthlyTotals->total_in ?? 0,
                    'total_out' => $monthlyTotals->total_out ?? 0,
                    'amount_in' => $monthlyTotals->amount_in ?? 0,
                    'amount_out' => $monthlyTotals->amount_out ?? 0
                ]
            ];
        }

        $cashRegisters = CashRegister::all();

        foreach ($cashRegisters as $register) {
            if ($register['primary']) {
                // Cargar movimientos solo para la principal
                $register->setRelation('movements', $register->movements);
            } else {
                // Eliminar relación para que no venga vacía o se cargue por accidente
                $register->setRelation('movements', collect());
            }
        }

        return $cashRegisters;
    }

    public function deposit($data)
    {

        $cashRegister = CashRegister::find($data['cash_register_id']);

        if (!$cashRegister) {
            return new \ErrorException("La caja de id: " . $data['cash_register_id'] . " no existe");
        }

        $data['type'] = 'in';
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }

        $last = $cashRegister->movements()->first();
        $previous_balance = $last ? $last->current_balance : 0;
        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance + $data['amount'];

        $cashRegister->balance =  $data['current_balance'];
        $cashRegister->save();

        return Movement::create($data);
    }

    public function withdraw($data)
    {

        $cashRegister = CashRegister::find($data['cash_register_id']);

        if (!$cashRegister) {
            return new \ErrorException("La caja de id: " . $data['cash_register_id'] . " no existe");
        }

        $data['type'] = 'out';
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }

        $last = $cashRegister->movements()->first();

        $previous_balance = $last ? $last->current_balance : 0;
        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance - $data['amount'];


        $cashRegister->balance =  $data['current_balance'];
        $cashRegister->save();

        return Movement::create($data);
    }

    public function transferMoney($data)
    {
        $data['type'] = 'transfer';
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = $data['amount'];
        }

        $from = CashRegister::find($data['from']);
        $to = CashRegister::find($data['to']);

        if ($data['amount'] > $from->balance) {
            throw new \Exception('Fondos insuficientes en la caja de origen');
        }

        // Movimiento en caja origen
        $lastFrom = $from->movements()->first();
        $previous_balance = $lastFrom ? $lastFrom->current_balance : 0;

        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance - $data['amount'];

        $amountFormat = number_format($data['amount'], 2, ',', '.');
        $amountFormat = rtrim($amountFormat, '0');
        $amountFormat = rtrim($amountFormat, ',');

        $data['description'] = 'Transferencia de $' . $amountFormat  . ' a la caja ' . $to->name;

        $from->balance = $data['current_balance'];
        $from->save();

        $data['cash_register_id'] = $from->id;

        $movementTransfer = Movement::create($data);

        // Movimiento en caja destino
        $data['type'] = 'in';

        $lastTo = $to->movements()->first();
        $previous_balance = $lastTo ? $lastTo->current_balance : 0;

        $data['previous_balance'] = $previous_balance;
        $data['current_balance'] = $previous_balance + $data['amount'];
        
        $data['description'] = 'Transferencia recibida de $' . $amountFormat  . ' desde la caja ' . $from->name;

        $to->balance = $data['current_balance'];
        $to->save();

        $data['cash_register_id'] = $to->id;

        $movementIn = Movement::create($data);

        return [
            'movement_transfer' => $movementTransfer,
            'movement_in' => $movementIn
        ];
    }
}
