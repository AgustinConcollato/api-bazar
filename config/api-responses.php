<?php

return [
    'success' => [
        'default' => ['status' => 'success', 'message' => 'Operación exitosa'],
        'created' => ['status' => 'success', 'message' => 'Producto creado correctamente'],
        'updated' => ['status' => 'success', 'message' => 'Producto actualizado correctamente'],
        'deleted' => ['status' => 'success', 'message' => 'Producto eliminado correctamente'],
    ],
    'error' => [
        'not_found' => ['status' => 'error', 'message' => 'No encontrado'],
        'validation' => ['status' => 'error', 'message' => 'Error de validación'],
        'server_error' => ['status' => 'error', 'message' => 'Error interno del servidor'],
    ],
];
