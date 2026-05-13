<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Casa de Apuestas API',
    description: 'Historias de usuario HU-01 a HU-05: registro, login, logout, perfil y moderación de cuentas.'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API versión 1'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token personal de acceso (Authorization: Bearer {token})'
)]
class OpenApiSpec
{
}
