<?php
// src/Support/NotificationsClient.php

/**
 * Envía un evento interno al microservicio de notificaciones.
 */
function send_internal_event(string $path, array $payload): void {
    // URL interna del micro de notificaciones dentro de Docker
    $baseUrl = getenv('NOTIFICACIONES_API_URL') ?: 'http://notificaciones-api:7001';
    $jwtSecret = getenv('JWT_SECRET') ?: 'super-secreto';

    $url = rtrim($baseUrl, '/') . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Internal-Token: ' . $jwtSecret,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log('Error enviando evento interno a notificaciones: ' . curl_error($ch));
        error_log('Status: ' . $status . ' Response: ' . $response);
    }

    curl_close($ch);
}

/**
 * Evento específico: user.registered
 */
function notify_user_registered(string $userId, string $email, string $name): void {
    $payload = [
        'user_id' => $userId,
        'email'   => $email,
        'name'    => $name,
    ];

    send_internal_event('/events/user-registered', $payload);
}
