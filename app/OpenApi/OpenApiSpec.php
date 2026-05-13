<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Casa de Apuestas API',
    description: 'API para casa de apuestas: autenticación, usuarios, deportes, eventos, cuotas, wallet y apuestas.'
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
    description: 'Token personal de acceso. Formato: Authorization: Bearer {token}'
)]
class OpenApiSpec
{
}
