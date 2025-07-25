<?php

namespace App\Services;

use App\Mail\ClientEmailVerificationCode;
use App\Models\Client;
use ErrorException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ClientService
{

    public function login($validated)
    {
        $client = Client::where('email', $validated['email'])
            ->with('address')
            ->first();

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

        if ($newClient && $validated['source'] === 'web') {
            return $this->login([...$validated, 'password' => $password]);
        } else {
            return $newClient;
        }
    }

    public function logout($request)
    {
        $client = $request->user('client');
        $client->currentAccessToken()->delete();
    }

    public function get($id = null, $source)
    {
        if ($id) {
            $client = Client::where(['id' => $id])
                ->with('address')
                ->with('orders.payments')
                ->with('payments', function ($query) {
                    $query->whereColumn('paid_amount', '<', 'expected_amount');
                })
                ->first();
            return $client;
        }

        if ($source) {
            $clients = Client::where('source', $source)->with('address')
                ->with('payments', function ($query) {
                    $query->whereColumn('paid_amount', '<', 'expected_amount');
                })
                ->get();
        } else {
            $clients = Client::with('address')->get();
        }

        return $clients;
    }

    public function verifyEmail($client)
    {
        $code = random_int(100000, 999999);

        $client["email_verification_code"] = $code;
        $client["email_verification_expires_at"] = now()->addMinutes(20);
        $client->save();

        $data = [
            'code' => $code,
            'name' => $client->name,
        ];

        Mail::to($client->email)->send(new ClientEmailVerificationCode($data));
    }

    public function verifyCode($client, $code)
    {
        if (!$client->email_verification_code || !$client->email_verification_expires_at) {
            throw new ErrorException('not code', 400);
        }

        if (now()->greaterThan($client->email_verification_expires_at)) {
            throw new ErrorException('code expired', 400);
        }

        if ($client->email_verification_code !== $code) {
            throw new ErrorException('invalid code', 400);
        }

        $client->email_verified_at = now();
        $client->email_verification_code = null;
        $client->email_verification_expires_at = null;
        $client->save();

        return $client;
    }

    public function updatePhone($client, $validated)
    {
        $client->phone_number = $validated['phone_number'];
        $client->save();

        return $client;
    }

    public function update($client, $validated)
    {

        if (isset($validated['email']) && $client->source !== 'dashboard') {
            unset($validated['email']);
            throw new ErrorException('No se pueden editar los datos de un cliente de la web', 403);
        }

        if (isset($validated['phone_number']) && $client->source !== 'dashboard') {
            unset($validated['phone_number']);
            throw new ErrorException('No se pueden editar los datos de un cliente de la web', 403);
        }

        if (isset($validated['name']) && $client->source !== 'dashboard') {
            unset($validated['name']);
            throw new ErrorException('No se pueden editar los datos de un cliente de la web', 403);
        }

        foreach ($validated as $key => $value) {
            $client->$key = $value;
        }

        $client->save();

        return $client;
    }

    public function search($clientName)
    {
        $clients = Client::where('name', 'like', '%' . $clientName . '%')->paginate(20);

        return $clients;
    }
}
