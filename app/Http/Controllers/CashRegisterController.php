<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CashRegisterController
{
    protected $cashRegisterService;
    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }
    public function get()
    {
        try {
            $response = $this->cashRegisterService->get();

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deposit(Request $request)
    {
        try {
            $validated = $request->validate([
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
}
