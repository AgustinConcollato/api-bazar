<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController
{
    public function get($userId)
    {

        $user = UserAddress::where('user_id', $userId)->get();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'No hay direcciones registradas'], 404);
        }

        return response()->json($user);
    }

    public function add(Request $request)
    {

        $data = $request->validate([
            'zip_code' => 'nullable|string',
            'status' => 'nullable|string',
            'user_id' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'code' => 'required|string',
            'address_number' => 'required|string',
        ]);

        $address = UserAddress::create($data);

        return response()->json($address, 201);
    }
    public function update(Request $request, $userId)
    {
        $code = $request->input('code');

        $address = UserAddress::where('user_id', $userId)
            ->where('status', 'selected')
            ->first();

        $address->update(['status' => null]);

        $newAddress = UserAddress::where('user_id', $userId)
            ->where('code', $code)
            ->first();

        $newAddress->update(['status' => 'selected']);

        $addresses = UserAddress::where('user_id', $userId)->get();

        return response()->json($addresses);
    }
}