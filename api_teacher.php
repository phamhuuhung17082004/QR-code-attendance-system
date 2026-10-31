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
    exit('Yêu cầu quản trị không hợp lệ.');
}

function teacher_redirect(bool $ok, string $message): void
{
    $class = $ok ? 'success' : 'danger';
    $_SESSION['msg'] = '<div class="alert alert-' . $class . ' mb-2" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    header('Location: teacher_details.php');
    exit();
}

$type = (string)($_GET['type'] ?? '');
if ($type === 'delete') {
    $id = filter_var($_POST['enroll'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    teacher_redirect((bool)$id && db_exec('DELETE FROM teachers WHERE id = ?', 'i', [(int)$id]) !== false, 'Đã xử lý yêu cầu xóa giáo viên.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$name = trim((string)($_POST['teachername'] ?? ''));
$education = trim((string)($_POST['education'] ?? ''));
$designation = trim((string)($_POST['designation'] ?? ''));
$branch = trim((string)($_POST['branch'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($name === '' || $education === '' || $designation === '' || $branch === '' || ($type === 'add' && strlen($password) < 8) || ($type === 'update' && $password !== '' && strlen($password) < 8)) {
    teacher_redirect(false, 'Dữ liệu không hợp lệ. Mật khẩu phải có ít nhất 8 ký tự.');
}

if ($type === 'add') {
    $ok = db_exec(
        'INSERT INTO teachers (name, education, designation, branch, password) VALUES (?, ?, ?, ?, ?)',
        'sssss',
        [$name, $education, $designation, $branch, password_hash($password, PASSWORD_DEFAULT)]
    ) !== false;
    teacher_redirect($ok, $ok ? 'Thêm giáo viên thành công.' : 'Không thể thêm giáo viên.');
}

if ($type === 'update' && $id) {
    if ($password === '') {
        $ok = db_exec('UPDATE teachers SET name = ?, education = ?, designation = ?, branch = ? WHERE id = ?', 'ssssi', [$name, $education, $designation, $branch, (int)$id]) !== false;
    } else {
        $ok = db_exec('UPDATE teachers SET name = ?, education = ?, designation = ?, branch = ?, password = ? WHERE id = ?', 'sssssi', [$name, $education, $designation, $branch, password_hash($password, PASSWORD_DEFAULT), (int)$id]) !== false;
    }
    teacher_redirect($ok, $ok ? 'Cập nhật giáo viên thành công.' : 'Không thể cập nhật giáo viên.');
}

teacher_redirect(false, 'Thao tác không hợp lệ.');
