<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 * title="Minha Loja API",
 * version="1.0.0",
 * description="Documentação dos endpoints REST da aplicação"
 * )
 *
 * @OA\Server(
 * url="/",
 * description="Servidor principal"
 * )
 *
 * @OA\SecurityScheme(
 * securityScheme="sanctum",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */
class SwaggerInfo
{
    // Classe vazia utilizada estritamente para ancorar a configuração global do Swagger.
}