<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
class ClientController
{
    //implementar esta protección en los demas controladores 
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function auth(Request $request)
    {
        try {
            $client = $request->user('client');

            return response()->json(['client' => $client]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Cliente no autenticado', 'error' => $e->getMessage()], 500);
        }
    }

    public function get($id = null)
    {

        try {
            $client = $this->clientService->get($id);

            return response()->json($client);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al buscar los datos', 'error' => $e->getMessage()], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('clients')->where(fn($query) => $query->where('source', 'web')),
                ],
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                ],
                'phone_number' => 'nullable|string|max:20',
            ]);

            $client = $this->clientService->register($validated);

            return response()->json($client, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al registrarse', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrarse', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $response = $this->clientService->login($validated);
            return response()->json($response, 200);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al iniciar sesión', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al iniciar sesión', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->clientService->logout($request);

            return response()->json(['message' => 'Sesión cerrada con éxito'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar sesión', 'error' => $e->getMessage()], 500);
        }

    }
}