<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Aplica CORS solo a estas rutas
    'allowed_methods' => ['*'],                  // Permite todos los métodos HTTP (GET, POST, PUT, DELETE, etc.)
    'allowed_origins' => ['*'],                  // Permite cualquier origen (peligroso en producción)
    'allowed_origins_patterns' => [],            // No estás usando expresiones regulares
    'allowed_headers' => ['*'],                  // Acepta cualquier encabezado
    'exposed_headers' => [],                     // No expone headers adicionales
    'max_age' => 0,                              // Desactiva el caché de preflight
    'supports_credentials' => false,             // NO permite cookies ni credenciales cruzadas
];

