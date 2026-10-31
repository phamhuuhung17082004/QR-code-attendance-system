<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Yêu cầu điểm danh thủ công không hợp lệ.');
}

$batch = trim((string)($_POST['batch'] ?? ''));
$semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$branch = trim((string)($_POST['branch'] ?? ''));
$subjectCode = filter_var($_POST['subject_code'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot = filter_var($_POST['slot'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$date = trim((string)($_POST['date'] ?? ''));
$timeInput = trim((string)($_POST['time'] ?? ''));
$students = $_POST['students'] ?? [];
$dateObject = DateTime::createFromFormat('Y-m-d', $date);

if ($batch === '' || !$semester || $branch === '' || !$subjectCode || !$slot || !$dateObject || $dateObject->format('Y-m-d') !== $date || !is_array($students)) {
    $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dữ liệu điểm danh không hợp lệ.</div>';
    header('Location: manual_attend.php');
    exit();
}

$timeObject = DateTime::createFromFormat('H:i', $timeInput);
if (!$timeObject || $timeObject->format('H:i') !== $timeInput) {
    $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Thời gian điểm danh không hợp lệ.</div>';
    header('Location: manual_attend.php');
    exit();
}

$success = [];
$failed = [];
$ipAddress = client_ip();
foreach (array_unique($students) as $value) {
    $enrollmentNo = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$enrollmentNo) {
        continue;
    }
    $student = db_row('SELECT enrollment_no FROM students WHERE batch = ? AND enrollment_no = ? AND semester = ? AND branch = ? LIMIT 1', 'siis', [$batch, (int)$enrollmentNo, (int)$semester, $branch]);
    if (!$student) {
        $failed[$enrollmentNo] = 'Sinh viên không thuộc lớp được chọn.';
        continue;
    }
    $exists = db_val('SELECT 1 FROM attendance WHERE date = ? AND enrollment_no = ? AND subject_code = ? AND slot = ? AND batch = ? LIMIT 1', 'siiis', [$date, (int)$enrollmentNo, (int)$subjectCode, (int)$slot, $batch]);
    if ($exists) {
        $failed[$enrollmentNo] = 'Sinh viên đã được điểm danh.';
        continue;
    }
    $saved = db_exec(
        'INSERT INTO attendance (enrollment_no, date, day, subject_code, slot, batch, branch, semester, time, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'issiississ',
        [(int)$enrollmentNo, $date, $dateObject->format('l'), (int)$subjectCode, (int)$slot, $batch, $branch, (int)$semester, $timeObject->format('H:i:s'), $ipAddress]
    );
    if ($saved === false) {
        $failed[$enrollmentNo] = 'Không thể ghi nhận điểm danh.';
    } else {
        $success[$enrollmentNo] = 'Điểm danh thành công.';
    }
}

$message = '<h5>Điểm danh thành công</h5><ol>';
foreach ($success as $id => $text) {
    $message .= '<li>Mã SV: ' . (int)$id . ' — ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';
}
$message .= '</ol><h5>Không thể điểm danh</h5><ol>';
foreach ($failed as $id => $text) {
    $message .= '<li>Mã SV: ' . (int)$id . ' — ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';
}
$message .= '</ol>';
$_SESSION['msg'] = '<div class="alert alert-info text-start mb-2" role="alert">' . $message . '</div>';
header('Location: manual_attend.php');
exit();
