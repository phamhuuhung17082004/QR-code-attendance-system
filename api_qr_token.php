<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/attendance.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function qr_token_response(int $status, bool $ok, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qr_token_response(405, false, 'Chỉ chấp nhận POST.');
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'TEACHER') {
    qr_token_response(401, false, 'Phiên đăng nhập đã hết hạn.');
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    qr_token_response(403, false, 'Yêu cầu không hợp lệ.');
}

$sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$sessionId) {
    qr_token_response(422, false, 'Phiên điểm danh không hợp lệ.');
}
$session = db_row(
    'SELECT * FROM attendance_sessions WHERE id = ? AND teacher_id = ? AND expires_at >= NOW() AND closed_at IS NULL LIMIT 1',
    'ii',
    [(int)$sessionId, (int)$_SESSION['teacher_id']]
);
if (!$session) {
    qr_token_response(404, false, 'Phiên điểm danh đã đóng hoặc đã hết hạn.');
}
if (!attendance_session_is_in_slot($session)) {
    qr_token_response(403, false, 'Chưa đến giờ hoặc đã hết giờ của tiết học; hệ thống không thể cấp QR.');
}

$token = attendance_new_qr_token((int)$sessionId);
if (!$token) {
    qr_token_response(503, false, 'Không thể tạo QR lúc này. Vui lòng thử lại.');
}

qr_token_response(200, true, 'OK', [
    'token' => $token,
    'expires_in' => max(30, min(180, (int)cfg('qr_rotate_seconds'))),
]);
