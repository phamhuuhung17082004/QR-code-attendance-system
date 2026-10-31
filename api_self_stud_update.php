<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();

// Kiểm tra đăng nhập và quyền SINH VIÊN
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    header('Location: login.php');
    exit();
}

require_csrf();

$enrollmentno = (int)$_SESSION['enrollment_no'];
$studentname = trim((string)($_POST['studentname'] ?? ''));

if ($studentname === '') {
    $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tên sinh viên không được để trống.</div>';
    header('Location: profile_stud.php');
    exit();
}

// Kiểm tra có tệp ảnh được tải lên không
if (isset($_FILES['pic']) && $_FILES['pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['pic']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tải tệp lên thất bại, vui lòng thử lại.</div>';
        header('Location: profile_stud.php');
        exit();
    }

    $tmpPath = (string)$_FILES['pic']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ['jpg', 'png', 'jpeg'], true)) {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Định dạng tệp không hợp lệ. Vui lòng tải lên tệp jpg, png hoặc jpeg.</div>';
        header('Location: profile_stud.php');
        exit();
    }

    // Kiểm tra thật là ảnh bằng getimagesize()
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false) {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tệp tải lên không phải là ảnh hợp lệ.</div>';
        header('Location: profile_stud.php');
        exit();
    }

    // Giữ cách đặt tên hiện tại (time()) nhưng thêm bytes ngẫu nhiên để không trùng
    $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $fileExtension;
    $uploadDir = __DIR__ . '/img/profile/';
    $uploadFile = $uploadDir . $filename;

    if (move_uploaded_file($tmpPath, $uploadFile)) {
        $result = db_exec(
            'UPDATE `students` SET `name` = ?, `pic` = ? WHERE `enrollment_no` = ?',
            'ssi',
            [$studentname, $filename, $enrollmentno]
        );

        if ($result !== false) {
            $_SESSION['username'] = $studentname;
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Thông tin sinh viên đã được cập nhật thành công.</div>';
            header('Location: profile_stud.php');
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi khi cập nhật cơ sở dữ liệu!</div>';
            header('Location: profile_stud.php');
            exit();
        }
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Lưu ảnh đại diện thất bại, vui lòng thử lại.</div>';
        header('Location: profile_stud.php');
        exit();
    }
} else {
    // Cập nhật tên sinh viên (không đổi ảnh)
    $result = db_exec(
        'UPDATE `students` SET `name` = ? WHERE `enrollment_no` = ?',
        'si',
        [$studentname, $enrollmentno]
    );

    if ($result !== false) {
        $_SESSION['username'] = $studentname;
        $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Thông tin sinh viên đã được cập nhật thành công.</div>';
        header('Location: profile_stud.php');
        exit();
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi khi cập nhật cơ sở dữ liệu!</div>';
        header('Location: profile_stud.php');
        exit();
    }
}
