<?php
/**
 * lib/passkey.php
 * Trợ giúp WebAuthn Passkey (xác thực sinh trắc học gắn thiết bị cho sinh viên).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/webauthn/autoload.php';

if (defined('QRCAS_PASSKEY_LOADED')) {
    return;
}
define('QRCAS_PASSKEY_LOADED', true);

function passkey_mode(): string
{
    $mode = (string)cfg('passkey_mode');
    return $mode === 'off' ? 'off' : 'always';
}

function passkey_is_https_or_localhost(): bool
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    $hostName = explode(':', $host)[0];
    if (in_array(strtolower($hostName), ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return $https;
}

function passkey_rp_id(): string
{
    global $env;
    if (!empty($env['WEBAUTHN_RP_ID'])) {
        return trim($env['WEBAUTHN_RP_ID']);
    }
    $serverName = (string)($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost');
    $serverName = explode(':', $serverName)[0];
    return $serverName !== '' ? $serverName : 'localhost';
}

function passkey_webauthn(): \lbuchs\WebAuthn\WebAuthn
{
    $rpName = 'QRCAS';
    $rpId = passkey_rp_id();
    return new \lbuchs\WebAuthn\WebAuthn($rpName, $rpId, null, true);
}

function passkey_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function passkey_base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return (string)base64_decode(strtr($data, '-_', '+/'));
}

function passkey_save_registration_challenge(int $enrollmentNo, $challenge): void
{
    security_start_session();
    $binary = $challenge instanceof \lbuchs\WebAuthn\Binary\ByteBuffer
        ? $challenge->getBinaryString()
        : (string)$challenge;
    $_SESSION['passkey_reg_challenge'] = [
        'enrollment_no' => $enrollmentNo,
        'challenge'     => $binary,
        'expires_at'    => time() + 180,
    ];
}

function passkey_consume_registration_challenge(int $enrollmentNo): ?string
{
    security_start_session();
    $data = $_SESSION['passkey_reg_challenge'] ?? null;
    unset($_SESSION['passkey_reg_challenge']);
    if (!is_array($data)
        || (int)($data['enrollment_no'] ?? 0) !== $enrollmentNo
        || (int)($data['expires_at'] ?? 0) < time()
        || empty($data['challenge'])) {
        return null;
    }
    return (string)$data['challenge'];
}

function passkey_save_auth_challenge(int $enrollmentNo, int $attendanceSessionId, $challenge): void
{
    security_start_session();
    $binary = $challenge instanceof \lbuchs\WebAuthn\Binary\ByteBuffer
        ? $challenge->getBinaryString()
        : (string)$challenge;
    $_SESSION['passkey_auth_challenge'] = [
        'enrollment_no'         => $enrollmentNo,
        'attendance_session_id' => $attendanceSessionId,
        'challenge'             => $binary,
        'expires_at'            => time() + 180,
    ];
}

function passkey_consume_auth_challenge(int $enrollmentNo, int $attendanceSessionId): ?string
{
    security_start_session();
    $data = $_SESSION['passkey_auth_challenge'] ?? null;
    unset($_SESSION['passkey_auth_challenge']);
    if (!is_array($data)
        || (int)($data['enrollment_no'] ?? 0) !== $enrollmentNo
        || (int)($data['attendance_session_id'] ?? 0) !== $attendanceSessionId
        || (int)($data['expires_at'] ?? 0) < time()
        || empty($data['challenge'])) {
        return null;
    }
    return (string)$data['challenge'];
}

function passkey_get_approved_credentials(int $enrollmentNo): array
{
    return db_all(
        "SELECT id, enrollment_no, credential_id, public_key, sign_count, label, status FROM student_passkeys WHERE enrollment_no = ? AND status = 'approved'",
        'i',
        [$enrollmentNo]
    );
}

function passkey_get_all_credentials_for_student(int $enrollmentNo): array
{
    return db_all(
        "SELECT id, enrollment_no, credential_id, public_key, sign_count, label, status FROM student_passkeys WHERE enrollment_no = ? AND status <> 'revoked'",
        'i',
        [$enrollmentNo]
    );
}

function passkey_count_active_credentials(int $enrollmentNo): int
{
    return (int)db_val(
        "SELECT COUNT(*) FROM student_passkeys WHERE enrollment_no = ? AND status <> 'revoked'",
        'i',
        [$enrollmentNo]
    );
}

function passkey_find_credential(string $rawCredentialId): ?array
{
    return db_row(
        "SELECT id, enrollment_no, credential_id, public_key, sign_count, label, status FROM student_passkeys WHERE credential_id = ? LIMIT 1",
        's',
        [$rawCredentialId]
    );
}

function passkey_store_credential(int $enrollmentNo, string $rawCredentialId, string $publicKeyPem, int $signCount, string $label, string $status): bool
{
    $approvedAt = $status === 'approved' ? date('Y-m-d H:i:s') : null;
    return db_exec(
        "INSERT INTO student_passkeys (enrollment_no, credential_id, public_key, sign_count, label, status, created_at, approved_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)",
        'ississs',
        [$enrollmentNo, $rawCredentialId, $publicKeyPem, $signCount, $label, $status, $approvedAt]
    ) !== false;
}

function passkey_update_usage(int $id, int $newSignCount): void
{
    db_exec(
        "UPDATE student_passkeys SET sign_count = ?, last_used_at = NOW() WHERE id = ?",
        'ii',
        [$newSignCount, $id]
    );
}

