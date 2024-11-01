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
        $factory = (new Factory)->withServiceAccount(base_path('bazar-regalaria-firebase-adminsdk.json'));
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
                $userList[] = [$user];
            }

            return response()->json($userList);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
