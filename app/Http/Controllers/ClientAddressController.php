<?php

namespace App\Http\Controllers;

use App\Models\ClientAddress;
use Illuminate\Http\Request;

class ClientAddressController
{
    public function get($userId)
    {

        $user = ClientAddress::where('client_id', $userId)->get();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'No hay direcciones registradas'], 404);
        }

        return response()->json($user);
    }

    public function add(Request $request)
    {
        try {
            $data = $request->validate([
                'zip_code' => 'nullable|string',
                'status' => 'nullable|string',
                'client_id' => 'required|string',
                'province' => 'required|string',
                'city' => 'required|string',
                'address' => 'required|string',
                'code' => 'required|string',
                'address_number' => 'required|string',
            ]);

            $address = ClientAddress::create($data);

            return response()->json($address, 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la dirección',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $userId)
    {
        $code = $request->input('code');

        $address = ClientAddress::where('client_id', $userId)
            ->where('status', 'selected')
            ->first();

        $address->update(['status' => null]);

        $newAddress = ClientAddress::where('client_id', $userId)
            ->where('code', $code)
            ->first();

        $newAddress->update(['status' => 'selected']);

        $addresses = ClientAddress::where('client_id', $userId)->get();

        return response()->json($addresses);
    }
}