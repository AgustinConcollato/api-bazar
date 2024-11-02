<?php

return [
    'success' => [
        'default' => ['status' => 'success', 'message' => 'Operación exitosa'],
        'created' => ['status' => 'success', 'message' => 'Creado correctamente'],
        'updated' => ['status' => 'success', 'message' => 'Actualizado correctamente'],
        'deleted' => ['status' => 'success', 'message' => 'Eliminado correctamente'],
        'authentication' => ['status' => 'success', 'message' => 'Autenticado correctamente'],
    ],
    'error' => [
        'not_found' => ['status' => 'error', 'message' => 'No encontrado'],
        'validation' => ['status' => 'error', 'message' => 'Error de validación'],
        'authentication' => ['status' => 'error', 'message' => 'Error de autenticación'],
        'server_error' => ['status' => 'error', 'message' => 'Error interno del servidor'],
    ],
];
