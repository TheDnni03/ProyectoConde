<?php
// src/bootstrap.php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use App\Middleware\JwtMiddleware;

$dotenv = Dotenv::createMutable(__DIR__ . '/..');
$dotenv->load();

$jwtSecret         = $_ENV['JWT_SECRET']        ?? 'secret';
$firebaseDbUrl     = $_ENV['FIREBASE_DB_URL']   ?? '';
$firebaseCredsPath = __DIR__ . '/../config/firebase_credentials.json';

if (!file_exists($firebaseCredsPath)) {
    die("No se encontró el archivo de credenciales de Firebase en: $firebaseCredsPath");
}

$factory = (new Factory())
    ->withServiceAccount($firebaseCredsPath)
    ->withDatabaseUri($firebaseDbUrl);

/** @var Database $database */
$database = $factory->createDatabase();

// Crear app Slim
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Registrar middleware de JWT
$jwtMiddleware = new JwtMiddleware($jwtSecret);

// Incluir rutas (comparten el scope de $app, $database, $jwtMiddleware, $jwtSecret)
require __DIR__ . '/Routes/auth.php';   // login / register / me
require __DIR__ . '/Routes/users.php';  // CRUD de usuarios

// Correr la app
$app->run();
