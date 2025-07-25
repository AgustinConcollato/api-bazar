<?php

namespace App\Http\Controllers;

use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashRegisterController
{
    protected $cashRegisterService;
    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'balance' => 'nullable|numeric'
            ], [
                'name.required' => 'El nombre de la caja es obligatorio'
            ]);

            $newCashRegister = $this->cashRegisterService->create($validated);

            return response()->json($newCashRegister);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function get($id = null)
    {
        try {
            $response = $this->cashRegisterService->get($id);

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deposit(Request $request)
    {
        try {
            $validated = $request->validate([
                'cash_register_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'date' => 'nullable|date',
                'method' => 'required|string|in:cash,transfer,check,other',
            ]);

            $deposit = $this->cashRegisterService->deposit($validated);

            return response()->json($deposit);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function withdraw(Request $request)
    {
        try {
            $validated = $request->validate([
                'cash_register_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'date' => 'nullable|date',
                'method' => 'required|string|in:cash,transfer,check,other',
            ]);

            $withdraw = $this->cashRegisterService->withdraw($validated);

            return response()->json($withdraw);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function transferMoney(Request $request)
    {
        try {
            $validated = $request->validate([
                'to' => 'required|string',
                'from' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'date' => 'nullable|date',
                'method' => 'required|string|in:cash,transfer,check,other',
            ]);

            $withdraw = $this->cashRegisterService->transferMoney($validated);

            return response()->json($withdraw);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
