<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Config;
use Illuminate\Http\Request;

class ClientController
{
    public function get($id = null)
    {
        if ($id) {
            $client = Client::find($id);

            return response()->json($client);
        }

        $clients = Client::get();

        return response()->json($clients);
    }

    public function add(Request $request)
    {
        $client = $request->validate([
            'name' => 'required|string',
            'id' => 'required|string',
        ]);

        Client::create($client);

        return response()->json(Config::get('api-responses.success.created'));
    }
}