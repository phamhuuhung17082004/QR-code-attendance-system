<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/face.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function face_enroll_response(int $status, bool $ok, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    face_enroll_response(405, false, 'Chỉ chấp nhận yêu cầu POST.');
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    face_enroll_response(401, false, 'Phiên đăng nhập đã hết hạn.');
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    face_enroll_response(403, false, 'Yêu cầu không hợp lệ. Hãy tải lại trang.');
}
if (($_POST['consent'] ?? '') !== '1') {
    face_enroll_response(422, false, 'Bạn cần xác nhận đồng ý đăng ký mẫu khuôn mặt.');
}

$descriptors = face_parse_descriptor_set($_POST['descriptors'] ?? null, 3, 8);
if ($descriptors === null) {
    face_enroll_response(422, false, 'Mẫu khuôn mặt không hợp lệ. Hãy thử lại bằng camera.');
}

$result = face_store_profile((int)$_SESSION['enrollment_no'], $descriptors);
face_enroll_response($result['ok'] ? 201 : 422, $result['ok'], $result['message']);
