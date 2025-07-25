<?php

namespace App\Http\Controllers;

use App\Services\MovementService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MovementController
{
    protected $movementService;
    public function __construct(MovementService $movementService)
    {
        $this->movementService = $movementService;
    }
    public function get()
    {
        try {
            $response = null;

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
