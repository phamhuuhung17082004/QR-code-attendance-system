<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/ui.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header('Location: login.php');
    exit();
}

function timetable_message(string $message, string $level = 'danger'): void
{
    $_SESSION['msg'] = '<div class="alert alert-' . $level . ' mb-2" role="alert">'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    header('Location: timetable.php');
    exit();
}

function timetable_filters(): ?array
{
    $academicYear = trim((string)($_POST['academicyear'] ?? ''));
    $branch = trim((string)($_POST['branch'] ?? ''));
    $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]]);
    $batch = trim((string)($_POST['batch'] ?? ''));
    $branches = ['Computer Engineering', 'Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering'];

    if (!preg_match('/^\d{4}-\d{2}$/', $academicYear)
        || !in_array($branch, $branches, true)
        || !$semester
        || $batch === ''
        || strlen($batch) > 100) {
        return null;
    }
    return [$academicYear, $branch, (int)$semester, $batch];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    timetable_message('Yêu cầu thời khóa biểu không hợp lệ. Vui lòng tải lại trang rồi thử lại.');
}

$type = (string)($_GET['type'] ?? '');
$filters = timetable_filters();
if (!$filters) {
    timetable_message('Hãy chọn đầy đủ năm học, ngành, học kỳ và lớp.');
}
[$academicYear, $branch, $semester, $batch] = $filters;

if ($type === 'delete') {
    $deleted = db_exec(
        'DELETE FROM timetable WHERE academic_year = ? AND branch = ? AND semester = ? AND batch = ?',
        'ssis',
        [$academicYear, $branch, $semester, $batch]
    );
    if ($deleted === false) {
        timetable_message('Không thể xóa thời khóa biểu. Vui lòng thử lại.');
    }
    timetable_message(
        $deleted > 0 ? 'Đã xóa thời khóa biểu.' : 'Không tìm thấy thời khóa biểu tương ứng.',
        $deleted > 0 ? 'success' : 'warning'
    );
}

if ($type !== 'add') {
    timetable_message('Thao tác không hợp lệ.');
}

$days = $_POST['day'] ?? [];
$slots = $_POST['slot'] ?? [];
$subjectCodes = $_POST['subject_code'] ?? [];
if (!is_array($days) || !is_array($slots) || !is_array($subjectCodes)
    || count($days) === 0 || count($days) !== count($slots) || count($days) !== count($subjectCodes)) {
    timetable_message('Hãy thêm ít nhất một buổi học hợp lệ.');
}

$validDays = array_keys(ui_days());
$slotLabels = ui_slot_labels();
$entries = [];
$seen = [];
foreach ($days as $index => $day) {
    $day = (string)$day;
    $slot = filter_var($slots[$index] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
    $subjectCode = filter_var($subjectCodes[$index] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!in_array($day, $validDays, true) || !$slot || !$subjectCode) {
        timetable_message('Có dòng lịch học chưa chọn đủ ngày, tiết hoặc môn học.');
    }
    $key = $day . ':' . $slot;
    if (isset($seen[$key])) {
        timetable_message('Một lớp không thể có hai môn trùng ngày và tiết.');
    }
    $seen[$key] = true;

    $subject = db_row(
        'SELECT subject_code FROM subjects WHERE subject_code = ? AND branch = ? AND semester = ? LIMIT 1',
        'isi',
        [(int)$subjectCode, $branch, $semester]
    );
    if (!$subject) {
        timetable_message('Có môn học không thuộc ngành hoặc học kỳ đã chọn.');
    }
    $entries[] = [$day, (int)$slot, (int)$subjectCode, $slotLabels[(int)$slot]];
}

if (db_val(
    'SELECT COUNT(*) FROM timetable WHERE academic_year = ? AND branch = ? AND semester = ? AND batch = ?',
    'ssis',
    [$academicYear, $branch, $semester, $batch]
) > 0) {
    timetable_message('Thời khóa biểu này đã tồn tại. Hãy xóa lịch cũ trước khi tạo lại.', 'warning');
}

$conn->begin_transaction();
try {
    foreach ($entries as [$day, $slot, $subjectCode, $slotLabel]) {
        if (db_exec(
            'INSERT INTO timetable (semester, batch, branch, day, slot, slotlabel, subject_code, academic_year)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isssisis',
            [$semester, $batch, $branch, $day, $slot, $slotLabel, $subjectCode, $academicYear]
        ) === false) {
            throw new RuntimeException('insert failed');
        }
    }
    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    timetable_message('Không thể lưu thời khóa biểu. Không có dữ liệu nào được thêm.');
}

$_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Đã tạo thời khóa biểu thành công.</div>';
header('Location: timetable.php?' . http_build_query([
    'academic' => $academicYear,
    'branch' => $branch,
    'semester' => $semester,
    'batch' => $batch,
]));
exit();
