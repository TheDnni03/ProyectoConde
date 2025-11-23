<?php
// src/routes/users.php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Support\Validator;

/**
 * @var App                           $app
 * @var \Kreait\Firebase\Database     $database
 * @var string                        $jwtSecret
 * @var \App\Middleware\JwtMiddleware $jwtMiddleware
 */

// =====================================================
//   GET /users  → obtener todos los usuarios
//   (protegido con JWT)
// =====================================================
$app->get('/users', function (Request $request, Response $response) use ($database) {
    $users = $database->getReference('users/ID')->getSnapshot()->getValue() ?? [];

    $result = [];
    foreach ($users as $id => $user) {
        // 👇 Filtramos nodos vacíos o no válidos
        if (!is_array($user) || $user === [] || $user === null) {
            continue;
        }

        if (isset($user['password'])) {
            unset($user['password']);
        }

        $result[] = [
            'id'   => (string)$id,
            'data' => $user,
        ];
    }

    $response->getBody()->write(json_encode([
        'users' => $result,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);

// =====================================================
//   GET /users/{id}  → obtener usuario por ID
//   (protegido con JWT)
// =====================================================
$app->get('/users/{id}', function (Request $request, Response $response, array $args) use ($database) {
    $id = $args['id'];

    $snapshot = $database->getReference('users/ID/' . $id)->getSnapshot();

    if (!$snapshot->exists() || $snapshot->getValue() === null) {
        $response->getBody()->write(json_encode([
            'error' => 'Usuario no encontrado.',
        ]));
        return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    $user = $snapshot->getValue();
    unset($user['password']);

    $response->getBody()->write(json_encode([
        'id'   => (string)$id,
        'user' => $user,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);

// =====================================================
//   PUT /users/{id}  → actualizar usuario
//   (protegido con JWT)
//   Campos permitidos: name, email, password
// =====================================================
$app->put('/users/{id}', function (Request $request, Response $response, array $args) use ($database) {
    $id   = $args['id'];
    $data = $request->getParsedBody() ?? [];

    $ref       = $database->getReference('users/ID/' . $id);
    $snapshot  = $ref->getSnapshot();

    if (!$snapshot->exists() || $snapshot->getValue() === null) {
        $response->getBody()->write(json_encode([
            'error' => 'Usuario no encontrado.',
        ]));
        return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    $currentUser = $snapshot->getValue();

    // Solo tomamos los campos que nos interesan
    $fields = array_intersect_key($data, array_flip(['name', 'email', 'password']));

    if (empty($fields)) {
        $response->getBody()->write(json_encode([
            'error' => 'No se envió ningún campo para actualizar.',
        ]));
        return $response->withStatus(400)
            ->withHeader('Content-Type', 'application/json');
    }

    // Reglas de validación dinámicas
    $rules    = [];
    $messages = [];

    if (array_key_exists('name', $fields)) {
        $rules['name'] = 'min:3';
        $messages['name.min'] = 'El nombre debe tener al menos :min caracteres.';
    }

    if (array_key_exists('email', $fields)) {
        $rules['email'] = 'email';
        $messages['email.email'] = 'El correo electrónico no es válido.';
    }

    if (array_key_exists('password', $fields)) {
        $rules['password'] = 'min:6';
        $messages['password.min'] = 'La contraseña debe tener al menos :min caracteres.';
    }

    if (!empty($rules)) {
        $validation = Validator::validate($fields, $rules, $messages);

        if (!$validation['valid']) {
            $response->getBody()->write(json_encode([
                'errors' => $validation['errors'],
            ]));
            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // Validar email único si se está cambiando
    if (array_key_exists('email', $fields) && isset($fields['email'])) {
        $newEmail = $fields['email'];

        // Solo buscamos duplicados si realmente cambió
        if (!isset($currentUser['email']) || strcasecmp($currentUser['email'], $newEmail) !== 0) {
            $allUsers = $database->getReference('users/ID')->getSnapshot()->getValue() ?? [];

            foreach ($allUsers as $otherId => $otherUser) {
                if ((string)$otherId === (string)$id) {
                    continue; // saltar el propio usuario
                }
                if (is_array($otherUser)
                    && isset($otherUser['email'])
                    && strcasecmp($otherUser['email'], $newEmail) === 0
                ) {
                    $response->getBody()->write(json_encode([
                        'error' => 'El email ya está en uso por otro usuario.',
                    ]));
                    return $response->withStatus(409)
                        ->withHeader('Content-Type', 'application/json');
                }
            }
        }
    }

    // Construir datos para actualizar
    $updateData = [];

    if (array_key_exists('name', $fields)) {
        $updateData['name'] = $fields['name'];
    }
    if (array_key_exists('email', $fields)) {
        $updateData['email'] = $fields['email'];
    }
    if (array_key_exists('password', $fields)) {
        $updateData['password'] = password_hash($fields['password'], PASSWORD_BCRYPT);
    }

    $updateData['updated_at'] = (new DateTimeImmutable())->format(DATE_ATOM);

    // Actualizar en Firebase
    $ref->update($updateData);

    // Volver a leer usuario actualizado
    $updated = $ref->getSnapshot()->getValue();
    unset($updated['password']);

    $response->getBody()->write(json_encode([
        'id'   => (string)$id,
        'user' => $updated,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);

// =====================================================
//   DELETE /users/{id}  → borrar usuario
//   (protegido con JWT)
// =====================================================
$app->delete('/users/{id}', function (Request $request, Response $response, array $args) use ($database) {
    $id  = $args['id'];
    $ref = $database->getReference('users/ID/' . $id);
    $snapshot = $ref->getSnapshot();

    if (!$snapshot->exists() || $snapshot->getValue() === null) {
        $response->getBody()->write(json_encode([
            'error' => 'Usuario no encontrado.',
        ]));
        return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    // Eliminar nodo
    $ref->remove();

    $response->getBody()->write(json_encode([
        'message' => 'Usuario eliminado correctamente.',
        'id'      => (string)$id,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);
