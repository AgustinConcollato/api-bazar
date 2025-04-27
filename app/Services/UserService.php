<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{

    public function login($validated)
    {
        Auth::guard('user')->attempt(['email' => $validated['email'], 'password' => $validated['password']]);

        $client = Auth::guard('user')->user();

        $token = $client->createToken('user_token')->plainTextToken;

        return ['token' => $token, 'client' => $client];

    }

    public function register($validated)
    {
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        return $user;
    }

    public function logout($request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
    }
}