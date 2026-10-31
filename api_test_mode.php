<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/security.php';

security_start_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Chỉ chấp nhận yêu cầu POST.');
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    exit('Bạn không có quyền thay đổi chế độ kiểm thử.');
}
require_csrf();

$enabled = ($_POST['enabled'] ?? '') === '1';
if (!cfg_set('attendance_test_mode', $enabled ? '1' : '0')) {
    $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Không thể lưu chế độ kiểm thử. Hãy kiểm tra đã chạy database_security_migration.sql chưa.</div>';
} else {
    $_SESSION['msg'] = $enabled
        ? '<div class="alert alert-warning mb-2" role="alert">Đã bật chế độ kiểm thử: có thể điểm danh ngoài giờ tiết, nhưng QR, GPS và khuôn mặt vẫn bắt buộc.</div>'
        : '<div class="alert alert-success mb-2" role="alert">Đã tắt chế độ kiểm thử. Hệ thống đã kiểm tra đúng giờ tiết trở lại.</div>';
}

header('Location: settings.php');
exit();
