<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientOwnsResource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el cliente autenticado
        $client = $request->user();

        // Verificar si el cliente está tratando de acceder a su propia información
        // Suponiendo que la ruta contiene un parámetro `clientId` que indica el cliente al que se refiere
        $clientId = $request->route('clientId'); // Cambia 'clientId' por el nombre del parámetro en tus rutas

        if ($client->id != $clientId) {
            return response()->json(['error' => 'No autorizado a acceder a esta información'], 403);
        }

        return $next($request);
    }
}