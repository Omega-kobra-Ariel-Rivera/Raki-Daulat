<?php
/**
 * POST /api/contacto.php
 * Guarda un mensaje del formulario de contacto.
 *
 * Campos: nombre, correo, telefono (opcional), mensaje.
 * Respuesta: JSON { ok, message, errors? }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

require_post();

$success = '¡Gracias! Recibimos tu mensaje y te responderemos a la brevedad.';
reject_bots($success);

$data = [
    'nombre'   => input('nombre'),
    'correo'   => input('correo'),
    'telefono' => input('telefono'),
    'mensaje'  => input('mensaje'),
];

$errors = [];
if (!text_length_between($data['nombre'], 2, 100)) {
    $errors['nombre'] = 'Ingresa tu nombre (entre 2 y 100 caracteres).';
}
if (!valid_email($data['correo'])) {
    $errors['correo'] = 'Ingresa un correo electrónico válido.';
}
if ($data['telefono'] !== '' && !valid_phone($data['telefono'])) {
    $errors['telefono'] = 'Ingresa un teléfono válido.';
}
if (!text_length_between($data['mensaje'], 10, 2000)) {
    $errors['mensaje'] = 'El mensaje debe tener entre 10 y 2000 caracteres.';
}

if ($errors) {
    json_response(422, ['ok' => false, 'message' => 'Revisa los campos marcados.', 'errors' => $errors]);
}

with_db(function (PDO $db) use ($data): void {
    $stmt = $db->prepare(
        'INSERT INTO contactos (nombre, correo, telefono, mensaje, ip)
         VALUES (:nombre, :correo, :telefono, :mensaje, :ip)'
    );
    $stmt->execute([
        ':nombre'   => $data['nombre'],
        ':correo'   => $data['correo'],
        ':telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
        ':mensaje'  => $data['mensaje'],
        ':ip'       => client_ip(),
    ]);
});

json_response(201, ['ok' => true, 'message' => $success]);
