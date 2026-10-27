<?php
/**
 * POST /api/reserva.php
 * Registra una solicitud de reserva (queda en estado "pendiente").
 *
 * Campos: nombre, correo, telefono, fecha (AAAA-MM-DD), hora (HH:MM),
 *         personas (1-12), comentarios (opcional).
 * Respuesta: JSON { ok, message, errors? }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

const MAX_PERSONAS   = 12;
const DIAS_ANTICIPO  = 90;       // se puede reservar hasta 3 meses antes
const DIA_CERRADO    = 1;        // 1 = lunes (formato ISO-8601, N)
const HORA_PRIMERA   = '11:00';
const HORA_ULTIMA    = '22:00';

require_post();

$success = '¡Reserva recibida! Te contactaremos para confirmarla.';
reject_bots($success);

$data = [
    'nombre'      => input('nombre'),
    'correo'      => input('correo'),
    'telefono'    => input('telefono'),
    'fecha'       => input('fecha'),
    'hora'        => input('hora'),
    'personas'    => input('personas'),
    'comentarios' => input('comentarios'),
];

$errors = [];

if (!text_length_between($data['nombre'], 2, 100)) {
    $errors['nombre'] = 'Ingresa tu nombre (entre 2 y 100 caracteres).';
}
if (!valid_email($data['correo'])) {
    $errors['correo'] = 'Ingresa un correo electrónico válido.';
}
if (!valid_phone($data['telefono'])) {
    $errors['telefono'] = 'Ingresa un teléfono válido.';
}

// Fecha: formato válido, desde hoy, dentro del plazo y no en día de cierre
$fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fecha']);
$hoy   = new DateTimeImmutable('today');
if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha']) {
    $errors['fecha'] = 'Elige una fecha válida.';
} elseif ($fecha < $hoy) {
    $errors['fecha'] = 'La fecha no puede ser anterior a hoy.';
} elseif ($fecha > $hoy->modify('+' . DIAS_ANTICIPO . ' days')) {
    $errors['fecha'] = 'Solo aceptamos reservas hasta con 3 meses de anticipación.';
} elseif ((int)$fecha->format('N') === DIA_CERRADO) {
    $errors['fecha'] = 'Los lunes el restaurante permanece cerrado.';
}

// Hora: bloques de 30 minutos dentro del horario de reservas
if (!preg_match('/^([01]\d|2[0-3]):(00|30)$/', $data['hora'])
    || $data['hora'] < HORA_PRIMERA || $data['hora'] > HORA_ULTIMA) {
    $errors['hora'] = 'Selecciona una hora entre ' . HORA_PRIMERA . ' y ' . HORA_ULTIMA . '.';
} elseif (!isset($errors['fecha']) && $fecha->format('Y-m-d') === $hoy->format('Y-m-d')
    && $data['hora'] <= date('H:i')) {
    $errors['hora'] = 'Esa hora ya pasó. Elige una hora posterior.';
}

$personas = filter_var($data['personas'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => MAX_PERSONAS],
]);
if ($personas === false) {
    $errors['personas'] = 'Indica entre 1 y ' . MAX_PERSONAS . ' personas.';
}

if (mb_strlen($data['comentarios']) > 1000) {
    $errors['comentarios'] = 'Los comentarios no pueden superar los 1000 caracteres.';
}

if ($errors) {
    json_response(422, ['ok' => false, 'message' => 'Revisa los campos marcados.', 'errors' => $errors]);
}

with_db(function (PDO $db) use ($data, $personas): void {
    $stmt = $db->prepare(
        'INSERT INTO reservas (nombre, correo, telefono, fecha, hora, personas, comentarios, ip)
         VALUES (:nombre, :correo, :telefono, :fecha, :hora, :personas, :comentarios, :ip)'
    );
    $stmt->execute([
        ':nombre'      => $data['nombre'],
        ':correo'      => $data['correo'],
        ':telefono'    => $data['telefono'],
        ':fecha'       => $data['fecha'],
        ':hora'        => $data['hora'],
        ':personas'    => $personas,
        ':comentarios' => $data['comentarios'] !== '' ? $data['comentarios'] : null,
        ':ip'          => client_ip(),
    ]);
});

json_response(201, ['ok' => true, 'message' => $success]);
