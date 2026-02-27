<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Metodo no permitido.']);
    exit;
}

require_once __DIR__ . '/config.php';

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
    http_response_code(422);
    echo json_encode(['message' => 'El nombre debe tener entre 2 y 80 caracteres.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['message' => 'El correo electronico no es valido.']);
    exit;
}

if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
    http_response_code(422);
    echo json_encode(['message' => 'El mensaje debe tener entre 10 y 2000 caracteres.']);
    exit;
}

$ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

try {
    $pdo = db_connection();
    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, message, ip_address, user_agent)
         VALUES (:name, :email, :message, :ip_address, :user_agent)'
    );

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'No se pudo guardar el mensaje. Revisa que MySQL este iniciado en MAMP.'
    ]);
}
