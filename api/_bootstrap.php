<?php
/**
 * Funciones compartidas por los endpoints de la API.
 *
 * - Carga la configuración desde variables de entorno (o desde el archivo .env).
 * - Abre la conexión PDO a PostgreSQL.
 * - Ofrece utilidades para responder JSON y validar datos.
 */

declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

/* ------------------------------------------------------------------ Configuración */

/**
 * Lee el archivo .env de la raíz del proyecto (si existe) y define
 * las variables que aún no estén configuradas en el entorno.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

load_env(dirname(__DIR__) . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'America/Lima'));

/* ------------------------------------------------------------------ Respuestas */

/** Envía una respuesta JSON y termina la ejecución. */
function json_response(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Acepta solo peticiones POST. */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        json_response(405, ['ok' => false, 'message' => 'Método no permitido.']);
    }
}

/**
 * Campo trampa (honeypot): si un bot lo completa, respondemos "éxito"
 * sin guardar nada para no darle pistas.
 */
function reject_bots(string $successMessage): void
{
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        json_response(200, ['ok' => true, 'message' => $successMessage]);
    }
}

/* ------------------------------------------------------------------ Base de datos */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        env('DB_HOST', 'localhost'),
        env('DB_PORT', '5432'),
        env('DB_NAME', 'raki_daulat')
    );

    $pdo = new PDO($dsn, env('DB_USER', 'postgres'), env('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/**
 * Ejecuta una función que usa la base de datos y transforma cualquier error
 * en una respuesta genérica (el detalle queda solo en el log del servidor).
 */
function with_db(callable $callback): mixed
{
    try {
        return $callback(db());
    } catch (PDOException $e) {
        error_log('[raki-daulat] Error de base de datos: ' . $e->getMessage());
        json_response(500, [
            'ok'      => false,
            'message' => 'Tenemos un problema técnico. Inténtalo más tarde o escríbenos por WhatsApp.',
        ]);
    }
}

/* ------------------------------------------------------------------ Validación */

/** Obtiene un campo de texto del formulario, sin espacios sobrantes. */
function input(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

function text_length_between(string $value, int $min, int $max): bool
{
    $len = mb_strlen($value);
    return $len >= $min && $len <= $max;
}

function valid_email(string $value): bool
{
    return mb_strlen($value) <= 150 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_phone(string $value): bool
{
    return (bool)preg_match('/^[0-9+()\s-]{7,20}$/', $value);
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}
