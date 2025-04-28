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
        $id = $request->input('id');

        $address = ClientAddress::where('client_id', $userId)
            ->where('status', 'selected')
            ->first();

        $address->update(['status' => null]);

        $newAddress = ClientAddress::where('client_id', $userId)
            ->where('id', $id)
            ->first();

        $newAddress->update(['status' => 'selected']);

        $addresses = ClientAddress::where('client_id', $userId)->get();

        return response()->json($addresses);
    }

    public function delete(Request $request, $addressId)
    {
        $client = $request->user('client');
        $address = ClientAddress::find($addressId);

        if (!$address) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }

        // Verificar si el status de la dirección es 'selected'
        if ($address->status === 'selected') {
            // Buscar la primera dirección que queda después de eliminar esta
            $nextAddress = ClientAddress::whereNull('status')
                ->where('client_id', $client->id)
                ->first();

            // Si encontramos una dirección, actualizar su status a 'selected'
            if ($nextAddress) {
                $nextAddress->status = 'selected';
                $nextAddress->save();
            }
        }

        $address->delete();

        $addresses = ClientAddress::where('client_id', $client->id)->get();

        return response()->json($addresses, 200);

    }
}