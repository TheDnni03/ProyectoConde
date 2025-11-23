<?php
// src/routes/auth.php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Support\Validator;
use Slim\App;

/**
 * @var App                           $app
 * @var \Kreait\Firebase\Database     $database
 * @var string                        $jwtSecret
 * @var \App\Middleware\JwtMiddleware $jwtMiddleware
 */

// ================== RUTA DE PRUEBA ==================
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'API Slim + Firebase funcionando'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ================== REGISTER ==================
$app->post('/register', function (Request $request, Response $response) use ($database) {
    $data = $request->getParsedBody() ?? [];

    // Validación tipo Laravel
    $validation = Validator::validate(
        $data,
        [
            'email'    => 'required|email',
            'password' => 'required|min:6',
            'name'     => 'required|min:3',
        ],
        [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos :min caracteres.',
            'name.required'     => 'El nombre es obligatorio.',
            'name.min'          => 'El nombre debe tener al menos :min caracteres.',
        ]
    );

    if (!$validation['valid']) {
        $response->getBody()->write(json_encode([
            'errors' => $validation['errors'],
        ]));
        return $response->withStatus(422)
            ->withHeader('Content-Type', 'application/json');
    }

    $email    = $data['email'];
    $password = $data['password'];
    $name     = $data['name'];

    // ===== NUEVA ESTRUCTURA: users/ID/{1,2,3,...} =====
    $usersRef = $database->getReference('users/ID');

    // Leer todos los usuarios para:
    // 1) Validar email duplicado
    // 2) Calcular el siguiente ID numérico
    $existingUsers = $usersRef->getSnapshot()->getValue() ?? [];

    // 1) Validar email duplicado (sin usar índices, filtramos en PHP)
    foreach ($existingUsers as $user) {
        if (isset($user['email']) && strcasecmp($user['email'], $email) === 0) {
            $response->getBody()->write(json_encode([
                'error' => 'El email ya está registrado.',
            ]));
            return $response->withStatus(409)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // 2) Calcular siguiente ID (1, 2, 3, ...)
    if (empty($existingUsers)) {
        $newId = 1;
    } else {
        $ids = array_map('intval', array_keys($existingUsers));
        $newId = max($ids) + 1;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Guardar usuario en users/ID/{newId}
    $usersRef->getChild((string)$newId)->set([
        'email'      => $email,
        'password'   => $hash,
        'name'       => $name,
        'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ]);

    $response->getBody()->write(json_encode([
        'id'    => $newId,
        'email' => $email,
        'name'  => $name,
    ]));
    return $response
        ->withStatus(201)
        ->withHeader('Content-Type', 'application/json');
});

// ================== LOGIN ==================
$app->post('/login', function (Request $request, Response $response) use ($database, $jwtSecret) {
    $data = $request->getParsedBody() ?? [];

    $validation = Validator::validate(
        $data,
        [
            'email'    => 'required|email',
            'password' => 'required',
        ],
        [
            'email.required'    => 'Debes ingresar tu correo electrónico.',
            'email.email'       => 'El correo electrónico no es válido.',
            'password.required' => 'Debes ingresar tu contraseña.',
        ]
    );

    if (!$validation['valid']) {
        $response->getBody()->write(json_encode([
            'errors' => $validation['errors'],
        ]));
        return $response->withStatus(422)
            ->withHeader('Content-Type', 'application/json');
    }

    $email    = $data['email'];
    $password = $data['password'];

    // NUEVA ESTRUCTURA: buscar en users/ID/{1,2,3,...}
    $allUsers = $database->getReference('users/ID')->getSnapshot()->getValue() ?? [];

    $userId   = null;
    $userData = null;

    foreach ($allUsers as $id => $user) {
        if (isset($user['email']) && strcasecmp($user['email'], $email) === 0) {
            $userId   = (string)$id;
            $userData = $user;
            break;
        }
    }

    if ($userId === null || $userData === null) {
        $response->getBody()->write(json_encode([
            'error' => 'Credenciales incorrectas.',
        ]));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    if (!password_verify($password, $userData['password'])) {
        $response->getBody()->write(json_encode([
            'error' => 'Credenciales incorrectas.',
        ]));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    // Generar JWT con el ID numérico
    $now = time();
    $payload = [
        'sub'   => $userId,
        'email' => $userData['email'],
        'iat'   => $now,
        'exp'   => $now + 60 * 60, // 1 hora
    ];

    $token = JWT::encode($payload, $jwtSecret, 'HS256');

    $response->getBody()->write(json_encode([
        'token' => $token,
        'user'  => [
            'id'    => $userId,
            'email' => $userData['email'],
            'name'  => $userData['name'] ?? null,
        ],
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ================== RUTA PROTEGIDA /me ==================
$app->get('/me', function (Request $request, Response $response) use ($database) {
    $token  = $request->getAttribute('token');
    $userId = $token->sub ?? null;

    if (!$userId) {
        $response->getBody()->write(json_encode([
            'error' => 'Token sin información de usuario (sub).',
        ]));
        return $response->withStatus(400)
            ->withHeader('Content-Type', 'application/json');
    }

    // Leer de users/ID/{userId}
    $snapshot = $database->getReference('users/ID/' . $userId)->getSnapshot();

    if (!$snapshot->exists()) {
        $response->getBody()->write(json_encode([
            'error' => 'Usuario no encontrado.',
        ]));
        return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    $user = $snapshot->getValue();
    unset($user['password']);

    $response->getBody()->write(json_encode([
        'user' => $user,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);
