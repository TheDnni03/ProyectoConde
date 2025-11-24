<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class JwtMiddleware
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error'   => 'Token requerido',
                'details' => 'El encabezado Authorization debe tener el formato: Bearer <token>',
            ]));

            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $tokenString = trim(substr($authHeader, 7));

        try {
            if ($this->secret === '') {
                throw new \RuntimeException('JWT_SECRET vacío en el middleware');
            }

            $decoded = JWT::decode($tokenString, new Key($this->secret, 'HS256'));

            // Guardamos el token decodificado en el request
            $request = $request->withAttribute('token', $decoded);

            return $handler->handle($request);
        } catch (\Throwable $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error'   => 'Token inválido',
                'details' => $e->getMessage(),
            ]));

            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
