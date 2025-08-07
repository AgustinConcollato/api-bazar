<?php

namespace App\Http\Controllers;

use App\Mail\ClientResetPassword;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as FacadesPassword;

class ClientController
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function auth(Request $request)
    {
        try {
            $client = $request->user('client');

            if ($client) {
                $client = Client::with('address')->find($client->id);
            }

            return response()->json(['client' => $client]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cliente no autenticado', 'error' => $e->getMessage()], 500);
        }
    }

    public function get(Request $request, $id = null)
    {
        try {
            $source = $request->input('source', null);
            $client = $this->clientService->get($id, $source);

            return response()->json($client);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al buscar los datos', 'error' => $e->getMessage()], 500);
        }
    }

    public function registerWeb(Request $request)
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
                        ->numbers()
                ],
                'phone_number' => 'nullable|string|max:20',
            ]);

            $validated['source'] = 'web';

            $client = $this->clientService->register($validated);

            return response()->json($client, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al registrarse', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrarse', 'error' => $e->getMessage()], 500);
        }
    }

    public function registerPanel(Request $request)
    {
        try {

            $validated =  $request->validate([
                'name' => 'required|string|max:100',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
                'phone_number' => 'nullable|string|max:20',
                'email' => 'nullable|email',
            ]);

            $validated['source'] = 'dashboard';

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

    public function updateEmail(Request $request)
    {
        try {
            $client = $request->user('client');

            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                    Rule::unique('clients')->where(fn($query) => $query->where('source', 'web'))->ignore($client->id)
                ]
            ]);

            $client->email = $validated['email'];
            $client->email_verified_at = null;
            $client->save();

            return response()->json(['message' => 'Correo actualizado con éxito', 'email' => $client->email]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar el correo', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el correo', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $client = $this->clientService->get($id, null);
            if (!$client) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            $messages = [
                'email.unique' => 'El correo electrónico ya está registrado por otro cliente.'
            ];

            $validated = $request->validate([
                'name' => 'nullable|string|max:100',
                'phone_number' => 'nullable|string|max:20',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('clients')->ignore($client->id)
                ],
            ], $messages);

            $client = $this->clientService->update($client, $validated);

            return response()->json($client, 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar el cliente', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el cliente', 'error' => $e->getMessage()], 500);
        }
    }

    public function search($clientName)
    {
        try {
            $clients = $this->clientService->search($clientName);
            return response()->json($clients);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al buscar clientes', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al buscar clientes', 'error' => $e->getMessage()], 500);
        }
    }

    public function sendEmailResetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $client = Client::where('email', $validated['email'])->first();
            if (!$client) {
                return response()->json(['error' => 'Cliente no encontrado'], 404);
            }

            $token = FacadesPassword::broker('clients')->createToken($client);
            \Illuminate\Support\Facades\Mail::to($validated['email'])->send(new ClientResetPassword($token, $validated['email']));

            return response()->json(['message' => 'Email enviado con token real']);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar el email de recuperación', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->letters()->numbers(),
                ],
            ]);

            $status = FacadesPassword::broker('clients')->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($client, $password) {
                    $client->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === FacadesPassword::PASSWORD_RESET) {
                $data = [
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                ];
                try {
                    $response = $this->clientService->login($data);
                    return response()->json($response, 200);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Error al iniciar sesión', 'error' => $e->getMessage()], 500);
                }
            }

            return response()->json([
                'message' => __($status),
            ], 422);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al restablecer la contraseña', 'error' => $e->getMessage()], 500);
        }
    }
}
