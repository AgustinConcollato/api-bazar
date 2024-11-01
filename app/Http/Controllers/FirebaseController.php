<?php
namespace App\Http\Controllers;

use Kreait\Firebase\Auth;
use Kreait\Firebase\Factory;
use Illuminate\Http\JsonResponse;

class FirebaseController
{
    protected $auth;

    public function __construct()
    {
        // Inicializa Firebase
        $factory = (new Factory)->withServiceAccount('path/to/your/firebase_credentials.json');
        $this->auth = $factory->createAuth();
    }

    public function getUsers(): JsonResponse
    {
        try {
            // Obtiene la lista de usuarios
            $users = $this->auth->listUsers();

            // Puedes formatear la respuesta según lo que necesites
            $userList = [];
            foreach ($users as $user) {
                $userList[] = [
                    'uid' => $user->uid,
                    'email' => $user->email,
                    'displayName' => $user->displayName,
                    'photoUrl' => $user->photoUrl,
                    'provider' => $user->providerData, // Información de proveedores
                ];
            }

            return response()->json($userList);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
