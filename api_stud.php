<?php
// Include PHPExcel
require 'PHPExcel/PHPExcel.php';
require 'PHPExcel/PHPExcel/IOFactory.php';
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
        $enrollmentno = filter_var($_POST['enrollmentno'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $studentname = trim((string)($_POST['studentname'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $branch = trim((string)($_POST['branch'] ?? ''));
        $rollno = filter_var($_POST['rollno'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $batch = trim((string)($_POST['batch'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (!$enrollmentno || !$semester || !$rollno || $studentname === '' || $branch === '' || $batch === '' || strlen($password) < 8) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dữ liệu không hợp lệ. Mật khẩu phải có ít nhất 8 ký tự.</div>';
            header('location: stud_details.php');
            exit();
        }
        $result = db_exec(
            'INSERT INTO students (enrollment_no, name, semester, branch, roll_no, batch, password) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'isisiss',
            [(int)$enrollmentno, $studentname, (int)$semester, $branch, (int)$rollno, $batch, password_hash($password, PASSWORD_DEFAULT)]
        ) !== false;
        if ($result) {
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Thêm sinh viên thành công.</div>';
            header("location: stud_details.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi, vui lòng thử lại!</div>';
            header("location: stud_details.php");
            exit();
        }
    }

    if (isset($_GET['type']) && $_GET['type'] == "delete") {
        $enroll = filter_var($_POST['enroll'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$enroll) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Mã số sinh viên không hợp lệ.</div>';
            header("location: stud_details.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            if (db_exec('DELETE FROM student_passkeys WHERE enrollment_no = ?', 'i', [(int)$enroll]) === false) {
                throw new RuntimeException('Không thể xóa passkey của sinh viên.');
            }
            if (db_exec('DELETE FROM student_face_profiles WHERE enrollment_no = ?', 'i', [(int)$enroll]) === false) {
                throw new RuntimeException('Không thể xóa hồ sơ khuôn mặt của sinh viên.');
            }
            if (db_exec('DELETE FROM students WHERE enrollment_no = ?', 'i', [(int)$enroll]) === false) {
                throw new RuntimeException('Không thể xóa sinh viên.');
            }
            $conn->commit();
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Xóa sinh viên thành công.</div>';
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('[QRCAS] Student delete failed: ' . $e->getMessage());
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi, vui lòng thử lại!</div>';
        }
        header("location: stud_details.php");
        exit();
    }

    if (isset($_GET['type']) && $_GET['type'] == "update") {
        $enrollmentno = filter_var($_POST['enrollmentno'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $studentname = trim((string)($_POST['studentname'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $branch = trim((string)($_POST['branch'] ?? ''));
        $rollno = filter_var($_POST['rollno'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $batch = trim((string)($_POST['batch'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (!$enrollmentno || !$semester || !$rollno || $studentname === '' || $branch === '' || $batch === '' || ($password !== '' && strlen($password) < 8)) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dữ liệu không hợp lệ. Mật khẩu mới phải có ít nhất 8 ký tự.</div>';
            header('location: stud_details.php');
            exit();
        }
        if ($password === '') {
            $result = db_exec('UPDATE students SET name = ?, semester = ?, branch = ?, roll_no = ?, batch = ? WHERE enrollment_no = ?', 'sisisi', [$studentname, (int)$semester, $branch, (int)$rollno, $batch, (int)$enrollmentno]) !== false;
        } else {
            $result = db_exec('UPDATE students SET name = ?, semester = ?, branch = ?, roll_no = ?, batch = ?, password = ? WHERE enrollment_no = ?', 'sisissi', [$studentname, (int)$semester, $branch, (int)$rollno, $batch, password_hash($password, PASSWORD_DEFAULT), (int)$enrollmentno]) !== false;
        }
        if ($result) {
            $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Cập nhật thông tin sinh viên thành công.</div>';
            header("location: stud_details.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi, vui lòng thử lại!</div>';
            header("location: stud_details.php");
            exit();
        }
    }

    if (isset($_GET['type']) && $_GET['type'] == "bulk" && isset($_FILES['file'])) {
        if (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tải file lên thất bại. Vui lòng thử lại.</div>';
            header("location: stud_details.php");
            exit();
        }

        // Kiểm tra phần mở rộng file không phân biệt hoa thường
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xls', 'xlsx'], true)) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Định dạng file không hợp lệ. Vui lòng tải lên file Excel (.xls hoặc .xlsx).</div>';
            header("location: stud_details.php");
            exit();
        }

        // Lưu file tạm với tên ngẫu nhiên
        $uploadDir = __DIR__ . '/uploads/';
        $randomName = 'bulk_' . bin2hex(random_bytes(16)) . '.' . $fileExtension;
        $uploadFile = $uploadDir . $randomName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            $expectedHeaders = ['enrollment', 'name', 'semester', 'branch', 'roll no', 'batch', 'password'];
            $redirectMsg = null;

            try {
                // Đọc file Excel
                $objPHPExcel = PHPExcel_IOFactory::load($uploadFile);
                $worksheet = $objPHPExcel->getSheet(0);
                $headerRow = $worksheet->getRowIterator(1)->current();
                $cellIterator = $headerRow->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $headers = [];
                foreach ($cellIterator as $cell) {
                    $headers[] = trim(strtolower((string)$cell->getValue()));
                }

                if ($headers !== $expectedHeaders) {
                    $redirectMsg = '<div class="alert alert-danger mb-2" role="alert">Cấu trúc file không hợp lệ. Vui lòng đảm bảo file Excel có đúng tiêu đề cột và đúng thứ tự.</div>';
                } else {
                    $stmt = $conn->prepare("INSERT INTO students (enrollment_no, name, semester, branch, roll_no, batch, password) VALUES (?, ?, ?, ?, ?, ?, ?)");

                    $firstRow = true;
                    foreach ($worksheet->getRowIterator() as $row) {
                        if ($firstRow) {
                            $firstRow = false;
                            continue;
                        }

                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);

                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }

                        if (empty($rowData[0])) {
                            continue;
                        }

                        $enrollment = (int)$rowData[0];
                        $name = (string)$rowData[1];
                        $semester = (int)$rowData[2];
                        $branch = (string)$rowData[3];
                        $rollno = (int)$rowData[4];
                        $batch = (string)$rowData[5];
                        $password = password_hash((string)$rowData[6], PASSWORD_DEFAULT);

                        $stmt->bind_param("isisiss", $enrollment, $name, $semester, $branch, $rollno, $batch, $password);
                        $stmt->execute();
                    }

                    $stmt->close();
                    $redirectMsg = '<div class="alert alert-success mb-2" role="alert">Nhập dữ liệu hàng loạt thành công.</div>';
                }
            } catch (Exception $e) {
                $redirectMsg = '<div class="alert alert-danger mb-2" role="alert">Lỗi khi đọc file. Vui lòng kiểm tra lại file và thử lại.</div>';
            } finally {
                // LUÔN xóa file (unlink) sau khi xử lý xong kể cả khi lỗi
                if (file_exists($uploadFile)) {
                    @unlink($uploadFile);
                }
            }

            if ($redirectMsg !== null) {
                $_SESSION['msg'] = $redirectMsg;
                header("location: stud_details.php");
                exit();
            }
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tải file lên thất bại. Vui lòng thử lại.</div>';
            header("location: stud_details.php");
            exit();
        }
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi hoặc chưa chọn file!</div>';
        header("location: stud_details.php");
        exit();
    }
} else {
    header("location: login.php");
    exit();
}
