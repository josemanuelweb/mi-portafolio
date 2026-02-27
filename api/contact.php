<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Metodo no permitido.']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

function send_auto_reply(string $toEmail, string $toName): void
{
    $enabled = strtolower(env_or_default('AUTO_REPLY_ENABLED', '1'));
    if (!in_array($enabled, ['1', 'true', 'yes', 'on'], true)) {
        return;
    }

    $siteName = env_or_default('SITE_NAME', 'Jose Manuel Portafolio');
    $fromEmail = env_or_default('MAIL_FROM', 'no-reply@localhost');
    $replyTo = env_or_default('MAIL_REPLY_TO', 'no-reply@localhost');
    $subject = env_or_default('AUTO_REPLY_SUBJECT', 'Recibimos tu mensaje');

    $safeName = preg_replace('/[\r\n]+/', ' ', $toName);
    $safeSiteName = preg_replace('/[\r\n]+/', ' ', $siteName);

    $body = "Hola {$safeName},\n\n";
    $body .= "Gracias por contactarte con {$safeSiteName}. Recibimos tu mensaje correctamente.\n";
    $body .= "Te voy a responder dentro de las proximas 24 horas.\n\n";
    $body .= "Saludos,\n{$safeSiteName}\n";

    // El envio de mail no debe romper el flujo de guardado del formulario.
    $sent = smtp_send_plain_text($toEmail, $safeName, $subject, $body);
    if (!$sent) {
        error_log('No se pudo enviar el auto-reply a ' . $toEmail);
    }
}

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

    send_auto_reply($email, $name);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'No se pudo guardar el mensaje. Revisa que MySQL este iniciado en MAMP.'
    ]);
}
