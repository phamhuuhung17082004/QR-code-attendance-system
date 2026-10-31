<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/attendance.php';
require_once __DIR__ . '/lib/face.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function face_challenge_response(int $status, bool $ok, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    face_challenge_response(405, false, 'Chỉ chấp nhận yêu cầu POST.');
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    face_challenge_response(401, false, 'Phiên đăng nhập đã hết hạn.');
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    face_challenge_response(403, false, 'Yêu cầu không hợp lệ. Hãy tải lại trang.');
}

$session = attendance_session_from_token(trim((string)($_POST['token'] ?? '')));
if (!$session) {
    face_challenge_response(410, false, 'Mã QR đã hết hạn. Hãy quét lại mã đang hiển thị.');
}
if (!attendance_session_is_in_slot($session)) {
    face_challenge_response(403, false, 'Đã ngoài thời gian điểm danh của tiết học này.');
}

$enrollmentNo = (int)$_SESSION['enrollment_no'];
if (face_mode() === 'off') {
    face_challenge_response(200, true, 'Không yêu cầu xác minh khuôn mặt.', ['required' => false]);
}
if (!face_profile($enrollmentNo)) {
    face_challenge_response(422, false, 'Bạn chưa đăng ký khuôn mặt. Vào Hồ sơ cá nhân để đăng ký trước khi điểm danh.');
}

$challenge = face_issue_challenge((int)$session['id'], $enrollmentNo);
face_challenge_response(200, true, 'Hãy hoàn thành thử thách khuôn mặt.', [
    'required' => true,
    'challenge_id' => $challenge['id'],
    'challenge' => $challenge['challenge'],
    'expires_in' => $challenge['expires_in'],
]);
