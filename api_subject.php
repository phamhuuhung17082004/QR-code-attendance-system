<?php
require "conn.php";
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';
security_start_session();

if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header("location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Yêu cầu quản trị không hợp lệ.');
}

if (isset($_SESSION['logged']) && $_SESSION['logged'] == true) {

    if (isset($_GET['type']) && $_GET['type'] == "add") {
        $subjectcode = filter_var($_POST['subjectcode'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $description = trim((string)($_POST['description'] ?? ''));
        $abbrevation = trim((string)($_POST['abbrevation'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $branch = trim((string)($_POST['branch'] ?? ''));
        $teacherid = filter_var($_POST['teacherid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$subjectcode || $description === '' || $abbrevation === '' || !$semester || $branch === '' || !$teacherid) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.</div>';
            header("location: subject_details.php");
            exit();
        }

        $result = db_exec(
            'INSERT INTO subjects (subject_code, name, abbreviation, semester, branch, teacher_id) VALUES (?, ?, ?, ?, ?, ?)',
            'issisi',
            [(int)$subjectcode, $description, $abbrevation, (int)$semester, $branch, (int)$teacherid]
        ) !== false;

        if ($result) {
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">
        Thêm môn học thành công.
        </div>';
            header("location: subject_details.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">
        Đã xảy ra lỗi, vui lòng thử lại!
        </div>';
            header("location: subject_details.php");
            exit();
        }
    }

    if (isset($_GET['type']) && $_GET['type'] == "delete") {
        $enroll = filter_var($_POST['enroll'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $result = $enroll && db_exec('DELETE FROM subjects WHERE subject_code = ?', 'i', [(int)$enroll]) !== false;
        if ($result) {
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">
        Xóa môn học thành công.
        </div>';
            header("location: subject_details.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">
        Đã xảy ra lỗi, vui lòng thử lại!
        </div>';
            header("location: subject_details.php");
            exit();
        }
    }

    if (isset($_GET['type']) && $_GET['type'] == "update") {
        $subjectcode = filter_var($_POST['subjectcode'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $description = trim((string)($_POST['description'] ?? ''));
        $abbrevation = trim((string)($_POST['abbrevation'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $branch = trim((string)($_POST['branch'] ?? ''));
        $teacherid = filter_var($_POST['teacherid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$subjectcode || $description === '' || $abbrevation === '' || !$semester || $branch === '' || !$teacherid) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.</div>';
            header("location: subject_details.php");
            exit();
        }

        $result = db_exec(
            'UPDATE subjects SET name = ?, abbreviation = ?, semester = ?, branch = ?, teacher_id = ? WHERE subject_code = ?',
            'ssisii',
            [$description, $abbrevation, (int)$semester, $branch, (int)$teacherid, (int)$subjectcode]
        ) !== false;

        if ($result) {
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">
        Cập nhật thông tin môn học thành công.
        </div>';
            header("location: subject_details.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">
        Đã xảy ra lỗi, vui lòng thử lại!
        </div>';
            header("location: subject_details.php");
            exit();
        }
    }
} else {
    header("location: login.php");
    exit();
}
