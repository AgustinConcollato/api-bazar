<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class ClientService
{

    public function login($validated)
    {
        $client = Client::where('email', $validated['email'])->first();

        if (!$client || !Hash::check($validated['password'], $client->password)) {
            throw new \Exception('Credenciales incorrectas', 401);
        }

        $token = $client->createToken('client_token')->plainTextToken;

        return ['token' => $token, 'client' => $client];

    }

    public function register($validated)
    {
        $password = $validated['password'];
        $validated['password'] = Hash::make($password);
        $newClient = Client::create($validated);

        if ($newClient) {
            return $this->login([...$validated, 'password' => $password]);
        }
    }

    public function logout($request)
    {
        $client = $request->user('client');
        $client->currentAccessToken()->delete();
    }

    public function get($id = null)
    {
        if ($id) {
            $client = Client::where(['id' => $id])->with('address');
            return $client;
        }

        $clients = Client::with('address')->get();

        return $clients;
    }
}