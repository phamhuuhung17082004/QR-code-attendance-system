<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function notification_response(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    notification_response(405, ['message' => 'Chỉ chấp nhận POST.']);
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'TEACHER') {
    notification_response(401, ['message' => 'Phiên đăng nhập đã hết hạn.']);
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    notification_response(403, ['message' => 'Yêu cầu không hợp lệ.']);
}

$subjectCode = filter_var($_POST['subject_code'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot = filter_var($_POST['slot'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$branch = trim((string)($_POST['branch'] ?? ''));
$batch = trim((string)($_POST['batch'] ?? ''));
if (!$subjectCode || !$semester || !$slot || $branch === '' || $batch === '') {
    notification_response(422, ['message' => 'Bộ lọc thông báo không hợp lệ.']);
}

$teacherId = (int)$_SESSION['teacher_id'];
$subject = db_row(
    'SELECT subject_code FROM subjects WHERE subject_code = ? AND branch = ? AND teacher_id = ? LIMIT 1',
    'isi',
    [(int)$subjectCode, $branch, $teacherId]
);
if (!$subject) {
    notification_response(403, ['message' => 'Bạn không có quyền xem nhật ký này.']);
}

$conn->begin_transaction();
try {
    $notifications = db_all(
        'SELECT n.*, COALESCE(s.name, \'\') AS name
         FROM attendance_noti n
         LEFT JOIN students s ON s.enrollment_no = n.enrollment_no
         WHERE n.is_read = 0 AND n.branch = ? AND n.batch = ? AND n.semester = ? AND n.slot = ? AND n.subject_code = ?
         ORDER BY n.id DESC
         LIMIT 100 FOR UPDATE',
        'ssiii',
        [$branch, $batch, (int)$semester, (int)$slot, (int)$subjectCode]
    );

    if ($notifications) {
        $ids = array_map(static function (array $notification): int {
            return (int)$notification['id'];
        }, $notifications);
        $updated = db_exec(
            'UPDATE attendance_noti SET is_read = 1 WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')',
            str_repeat('i', count($ids)),
            $ids
        );
        if ($updated === false) {
            throw new RuntimeException('Unable to mark notifications as read.');
        }
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[QRCAS] Notification polling failed: ' . $e->getMessage());
    notification_response(503, ['message' => 'Không thể tải nhật ký điểm danh.']);
}

notification_response(200, $notifications);
