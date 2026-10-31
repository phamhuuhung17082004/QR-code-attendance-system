<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Chỉ chấp nhận yêu cầu POST.');
}

require_csrf();

$action = trim((string)($_POST['action'] ?? ''));
$isJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

function admin_passkey_reply(bool $ok, string $message, bool $isJson) {
    if ($isJson) {
        http_response_code($ok ? 200 : 400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $_SESSION['msg'] = '<div class="alert ' . ($ok ? 'alert-success' : 'alert-danger') . ' mb-2" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    header('Location: passkey_admin.php');
    exit();
}

if ($action === 'approve') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        admin_passkey_reply(false, 'Mã định danh thiết bị không hợp lệ.', $isJson);
    }
    $adminId = (int)($_SESSION['teacher_id'] ?? 0);
    $res = db_exec(
        "UPDATE student_passkeys SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ? AND status = 'pending'",
        'ii',
        [$adminId, (int)$id]
    );
    if ($res === false) {
        admin_passkey_reply(false, 'Không thể duyệt thiết bị. Hãy kiểm tra cơ sở dữ liệu.', $isJson);
    } elseif ($res === 0) {
        admin_passkey_reply(false, 'Thiết bị không ở trạng thái chờ duyệt.', $isJson);
    } else {
        admin_passkey_reply(true, 'Đã duyệt thiết bị thành công.', $isJson);
    }
} elseif ($action === 'revoke') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        admin_passkey_reply(false, 'Mã định danh thiết bị không hợp lệ.', $isJson);
    }
    $res = db_exec(
        "UPDATE student_passkeys SET status = 'revoked' WHERE id = ?",
        'i',
        [(int)$id]
    );
    if ($res !== false) {
        admin_passkey_reply(true, 'Đã thu hồi quyền sử dụng thiết bị.', $isJson);
    } else {
        admin_passkey_reply(false, 'Không thể thu hồi thiết bị.', $isJson);
    }
} elseif ($action === 'delete_face') {
    $enrollmentNo = filter_var($_POST['enrollment_no'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$enrollmentNo) {
        admin_passkey_reply(false, 'Mã số sinh viên không hợp lệ.', $isJson);
    }
    $res = db_exec(
        "DELETE FROM student_face_profiles WHERE enrollment_no = ?",
        'i',
        [(int)$enrollmentNo]
    );
    if ($res !== false) {
        admin_passkey_reply(true, 'Đã xóa mẫu khuôn mặt của sinh viên ' . $enrollmentNo . '. Sinh viên có thể đăng ký lại mẫu mới.', $isJson);
    } else {
        admin_passkey_reply(false, 'Không thể xóa mẫu khuôn mặt của sinh viên.', $isJson);
    }
} else {
    admin_passkey_reply(false, 'Hành động không hợp lệ.', $isJson);
}

