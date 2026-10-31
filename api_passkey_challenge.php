<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/attendance.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/passkey.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function passkey_challenge_response(int $status, bool $ok, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    passkey_challenge_response(405, false, 'Chỉ chấp nhận yêu cầu POST.');
}

if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    passkey_challenge_response(401, false, 'Phiên đăng nhập đã hết hạn.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    passkey_challenge_response(403, false, 'Yêu cầu không hợp lệ. Hãy tải lại trang.');
}

// 1. Kiểm tra IP Wi-Fi của trường (Hạng mục 2)
$campusCidrs = trim((string)cfg('campus_cidrs'));
if ($campusCidrs !== '') {
    $ip = client_ip();
    if (!ip_in_cidrs($ip, $campusCidrs)) {
        passkey_challenge_response(403, false, 'Hãy kết nối Wi-Fi của trường để điểm danh.');
    }
}

// 2. Kiểm tra token QR
$token = trim((string)($_POST['token'] ?? ''));
$session = attendance_session_from_token($token);
if (!$session) {
    passkey_challenge_response(410, false, 'Mã QR đã hết hạn. Hãy quét lại mã đang hiển thị.');
}

// 3. Kiểm tra slot
if (!attendance_session_is_in_slot($session)) {
    passkey_challenge_response(403, false, 'Đã ngoài thời gian điểm danh của tiết học này.');
}

// 4. Kiểm tra passkey_mode
if (passkey_mode() === 'off') {
    passkey_challenge_response(200, true, 'Không yêu cầu passkey.', ['required' => false]);
}

if (!passkey_is_https_or_localhost()) {
    passkey_challenge_response(400, false, 'Passkey cần HTTPS.');
}

$enrollmentNo = (int)$_SESSION['enrollment_no'];
$approvedCreds = passkey_get_approved_credentials($enrollmentNo);
if (empty($approvedCreds)) {
    passkey_challenge_response(422, false, 'Thiết bị của bạn chưa được đăng ký hoặc chưa được quản trị viên duyệt.');
}

try {
    $webauthn = passkey_webauthn();
    $credIds = array_column($approvedCreds, 'credential_id');
    $getArgs = $webauthn->getGetArgs($credIds, 60, true, true, true, true, true, 'required');
    passkey_save_auth_challenge($enrollmentNo, (int)$session['id'], $webauthn->getChallenge());

    passkey_challenge_response(200, true, 'Hãy xác nhận sinh trắc học trên thiết bị.', [
        'required'   => true,
        'challenge'  => $getArgs,
        'expires_in' => 180,
    ]);
} catch (\Exception $e) {
    error_log('[passkey_challenge] ' . $e->getMessage());
    passkey_challenge_response(500, false, 'Không thể tạo thử thách xác thực thiết bị. Vui lòng thử lại.');
}

