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
            $source = $request->input('source', 'web');

            $rules = [
                'name' => 'required|string|max:100',
                'source' => 'nullable|in:web,dashboard',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
                'phone_number' => 'nullable|string|max:20',
            ];

            // Si es web, requerimos email único dentro de web
            if ($source === 'web') {
                $rules['email'] = [
                    'required',
                    'email',
                    Rule::unique('clients')->where(fn($query) => $query->where('source', 'web')),
                ];
            } else {
                // En dashboard el email puede ser null
                $rules['email'] = ['nullable', 'email'];
            }

            $validated = $request->validate($rules);

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

    public function verifyEmail(Request $request)
    {
        try {
            $client = $request->user('client');
            $this->clientService->verifyEmail($client);

            return response()->json(['message' => 'Código de verificación enviado'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar el código de verificación', 'error' => $e->getMessage()], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $client = $request->user('client');
            $client = $this->clientService->verifyCode($client, $request->code);

            return response()->json($client, 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al verificar la cuenta con el código', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al verificar la cuenta con el código', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePhone(Request $request)
    {
        try {
            $client = $request->user('client');
            $validated = $request->validate([
                'phone_number' => 'required|string',
            ]);

            $client = $this->clientService->updatePhone($client, $validated);

            return response()->json($client, 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar el número de teléfono', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el número de teléfono', 'error' => $e->getMessage()], 500);
        }
    }
}