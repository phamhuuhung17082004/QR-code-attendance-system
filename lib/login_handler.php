<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

security_start_session();

if (!isset($_GET['type'])) {
    return;
}

function login_fail(string $identity): void
{
    login_record_failure($identity, client_ip());
    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Thông tin đăng nhập không hợp lệ.</div>';
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Yêu cầu đăng nhập không hợp lệ. Vui lòng thử lại.</div>';
    header('Location: login.php');
    exit();
}

$type = (string)$_GET['type'];
$password = trim((string)($_POST['password'] ?? ''));
if ($password === '') {
    login_fail('empty:' . $type);
}

if ($type === 'admin') {
    $username = trim((string)($_POST['username'] ?? ''));
    $identity = 'admin:' . $username;
    if ($username === '' || login_is_locked($identity, client_ip())) {
        login_fail($identity);
    }
    $account = db_row('SELECT * FROM users WHERE username = ? AND user_type = ? AND status = 1 LIMIT 1', 'ss', [$username, 'ADMIN']);
    if (!$account || !verify_password_compat($password, (string)$account['password'])) {
        login_fail($identity);
    }
    if (!password_is_hashed((string)$account['password']) || password_needs_rehash((string)$account['password'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password = ? WHERE user_id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$account['user_id']]);
    }
    login_clear_failures($identity, client_ip());
    set_logged_in_session($account, 'ADMIN');
    header('Location: ./');
    exit();
}

if ($type === 'teacher') {
    $teacherId = filter_var($_POST['teacherid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $identity = 'teacher:' . (string)($_POST['teacherid'] ?? '');
    if (!$teacherId || login_is_locked($identity, client_ip())) {
        login_fail($identity);
    }
    $account = db_row('SELECT * FROM teachers WHERE id = ? LIMIT 1', 'i', [(int)$teacherId]);
    if (!$account || !verify_password_compat($password, (string)$account['password'])) {
        login_fail($identity);
    }
    if (!password_is_hashed((string)$account['password']) || password_needs_rehash((string)$account['password'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE teachers SET password = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$teacherId]);
    }
    login_clear_failures($identity, client_ip());
    set_logged_in_session($account, 'TEACHER');
    header('Location: ./');
    exit();
}

if ($type === 'student') {
    $enrollmentNo = filter_var($_POST['enroll'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $identity = 'student:' . (string)($_POST['enroll'] ?? '');
    if (!$enrollmentNo || login_is_locked($identity, client_ip())) {
        login_fail($identity);
    }
    $account = db_row('SELECT * FROM students WHERE enrollment_no = ? LIMIT 1', 'i', [(int)$enrollmentNo]);
    if (!$account || !verify_password_compat($password, (string)$account['password'])) {
        login_fail($identity);
    }
    if (!password_is_hashed((string)$account['password']) || password_needs_rehash((string)$account['password'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE students SET password = ? WHERE enrollment_no = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$enrollmentNo]);
    }
    login_clear_failures($identity, client_ip());
    set_logged_in_session($account, 'STUDENT');
    header('Location: ./');
    exit();
}

http_response_code(404);
exit('Không tìm thấy loại tài khoản.');
