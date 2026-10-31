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

$subject_code = filter_var($_GET['subject_code'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot = filter_var($_GET['slot'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$semester = filter_var($_GET['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$batch = trim((string)($_GET['batch'] ?? ''));
$branch = trim((string)($_GET['branch'] ?? ''));
$currentDay = trim((string)($_GET['day'] ?? ''));

$validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if (!$subject_code || !$slot || !$semester || $batch === '' || $branch === '' || !in_array($currentDay, $validDays, true)) {
    $_SESSION['msg'] = '<div class="alert alert-danger">Tham số điểm danh thủ công không hợp lệ.</div>';
    header('Location: manual_attend.php');
    exit();
}

$fsrow = db_row('SELECT * FROM `subjects` WHERE `subject_code` = ? LIMIT 1', 'i', [(int)$subject_code]);
if (!$fsrow) {
    $_SESSION['msg'] = '<div class="alert alert-danger">Không tìm thấy môn học hợp lệ.</div>';
    header('Location: manual_attend.php');
    exit();
}

$timetable = db_row(
    'SELECT * FROM `timetable` WHERE `subject_code` = ? AND `semester` = ? AND `branch` = ? AND `day` = ? AND `slot` = ? AND `batch` = ? LIMIT 1',
    'iissis',
    [(int)$subject_code, (int)$semester, $branch, $currentDay, (int)$slot, $batch]
);

if (!$timetable) {
    require('header.php');
    echo '<script>
        alert("Không có tiết học nào được phân công vào ' . htmlspecialchars($currentDay, ENT_QUOTES, 'UTF-8') . '!");
        window.location.href ="manual_attend.php";
    </script>';
    require('footer.php');
    exit();
}

$studentsList = db_all(
    'SELECT * FROM `students` WHERE `semester` = ? AND `branch` = ? AND `batch` = ? ORDER BY `enrollment_no` ASC',
    'iss',
    [(int)$semester, $branch, $batch]
);

require('header.php');
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Điểm danh</li>
            <li class="breadcrumb-item">Điểm danh thủ công</li>
        </ol>
    </nav>
</div>

<!-- Nội dung chính Bắt đầu -->
<div class="container-fluid pt-4 px-4">
    <div class="text-center w-100">
        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>
    </div>

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Thực hiện điểm danh</h6>

                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Môn học</td>
                            <td>Nhóm</td>
                            <td>Học kỳ</td>
                            <td>Khoa/Ngành</td>
                            <td>Ngày điểm danh</td>
                            <td>Ca học</td>
                        </tr>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($fsrow['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($batch, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$semester, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($branch, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($currentDay, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$slot, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <form action="api_manual_attend.php" method="post">
                    <?php echo csrf_input(); ?>
                    <input class="form-control" type="text" name="batch" value="<?php echo htmlspecialchars($batch, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                    <input class="form-control" type="text" name="semester" value="<?php echo htmlspecialchars((string)$semester, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                    <input class="form-control" type="text" name="branch" value="<?php echo htmlspecialchars($branch, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                    <input class="form-control" type="text" name="slot" value="<?php echo htmlspecialchars((string)$slot, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                    <input class="form-control" type="text" name="subject_code" value="<?php echo htmlspecialchars((string)$subject_code, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                    <input class="form-control" type="text" id="currentDay" name="currentDay" value="<?php echo htmlspecialchars($currentDay, ENT_QUOTES, 'UTF-8'); ?>" readonly hidden>
                    <div class="mb-3">
                        <label for="">Chọn ngày</label>
                        <input class="form-control" type="date" name="date" required>
                    </div>
                    <p class="text-danger">*Lưu ý: Vui lòng chọn ngày trùng với thứ trong tuần được hiển thị ở bảng trên.</p>
                    <div class="mb-3">
                        <label for="">Chọn giờ</label>
                        <input class="form-control" type="time" name="time" required>
                    </div>

                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th scope="col">Đánh dấu điểm danh</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Mã số sinh viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($studentsList as $studrow) {
                            ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="students[]" value="<?php echo htmlspecialchars((string)$studrow['enrollment_no'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars((string)$studrow['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$studrow['enrollment_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-success">Xác nhận</button>
                </form>
            </div>
        </div>

    </div>
</div>
<!-- Nội dung chính Kết thúc -->

<?php
require('footer.php');
?>
