<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    session_destroy();
    header("location: login.php");
    exit();
}

$sql = "SELECT * FROM `attendance` ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Ánh xạ tên ngày tiếng Anh sang tiếng Việt
$daysVI = [
    'Monday'    => 'Thứ Hai',
    'Tuesday'   => 'Thứ Ba',
    'Wednesday' => 'Thứ Tư',
    'Thursday'  => 'Thứ Năm',
    'Friday'    => 'Thứ Sáu',
    'Saturday'  => 'Thứ Bảy',
    'Sunday'    => 'Chủ Nhật',
];
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Điểm danh</li>
            <li class="breadcrumb-item">Xem điểm danh</li>
        </ol>
    </nav>
</div>


<!-- Nội dung chính bắt đầu -->
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
                <h6 class="mb-4">Xem điểm danh</h6>
                <form class="row gy-2 gx-3 align-items-center border p-2 mb-4" action="view_admin_attend.php" method="get">
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="" for="startdate">Từ ngày</label>
                        <input type="date" name="startdate" id="startdate" class="form-control" required>
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="" for="enddate">Đến ngày</label>
                        <input type="date" name="enddate" id="enddate" class="form-control" required>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Môn học</label>
                        <select class="form-select" name="subject" id="autoSizingSelect" required>
                            <option value="">-- Chọn môn học --</option>
                            <?php
                            $sssql = "SELECT * FROM `subjects`";
                            $ssresult = mysqli_query($conn, $sssql);
                            while ($erow = mysqli_fetch_array($ssresult)) {
                                echo '<option value="' . (int)$erow['subject_code'] . '">' . ui_h((string)$erow['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Lớp</label>
                        <select class="form-select" name="batch" id="autoSizingSelect" required>
                            <option value="">-- Chọn lớp --</option>
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="A3">A3</option>
                            <option value="A4">A4</option>
                            <option value="A5">A5</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Ngành</label>
                        <select class="form-select" name="branch" id="autoSizingSelect" required>
                            <option value="">-- Chọn ngành --</option>
                            <option value="Computer Engineering">Kỹ thuật Máy tính</option>
                            <option value="Mechanical Engineering">Kỹ thuật Cơ khí</option>
                            <option value="Electrical Engineering">Kỹ thuật Điện</option>
                            <option value="Civil Engineering">Kỹ thuật Xây dựng</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-success">Tìm kiếm</button>
                    </div>
                </form>

                <?php
                if (isset($_GET['startdate']) && isset($_GET['enddate']) && isset($_GET['subject']) && isset($_GET['batch']) && isset($_GET['branch'])) {
                    $sdateString = trim((string)$_GET['startdate']);
                    $edateString = trim((string)$_GET['enddate']);
                    $subject_code = filter_var($_GET['subject'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    $batch = trim((string)$_GET['batch']);
                    $branch = trim((string)$_GET['branch']);

                    $sdt = $sdateString !== '' ? DateTime::createFromFormat('Y-m-d', $sdateString) : false;
                    $sdateValid = ($sdt && $sdt->format('Y-m-d') === $sdateString);

                    $edt = $edateString !== '' ? DateTime::createFromFormat('Y-m-d', $edateString) : false;
                    $edateValid = ($edt && $edt->format('Y-m-d') === $edateString);

                    $batchValid = in_array($batch, ['A1', 'A2', 'A3', 'A4', 'A5'], true);
                    $branchValid = in_array($branch, ['Computer Engineering', 'Mechanical Engineering', 'Electrical Engineering', 'Civil Engineering'], true);

                    if (!$sdateValid || !$edateValid || $subject_code === false || !$batchValid || !$branchValid) {
                        echo '<div class="alert alert-danger mb-4" role="alert">Dữ liệu lọc không hợp lệ. Vui lòng kiểm tra lại ngày, môn học, lớp và ngành.</div>';
                    } else {
                        $stmt = $conn->prepare("SELECT * FROM attendance WHERE STR_TO_DATE(`date`, '%Y-%m-%d') BETWEEN STR_TO_DATE(?, '%Y-%m-%d') AND STR_TO_DATE(?, '%Y-%m-%d') AND `subject_code` = ? AND `batch` = ? AND `branch` = ? ORDER BY id DESC");
                        $stmt->bind_param('ssiss', $sdateString, $edateString, $subject_code, $batch, $branch);
                        $stmt->execute();
                        $result = $stmt->get_result();
                ?>
                    <h6>Kết quả lọc: Từ ngày <?php echo ui_h((string)$_GET['startdate']); ?> đến ngày <?php echo ui_h((string)$_GET['enddate']); ?></h6>
                    <table class="table table-bordered table-striped text-center attendance-history-table" id="table">
                        <thead>
                            <tr>
                                <th scope="col">STT</th>
                                <th scope="col">Mã sinh viên</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Ngày</th>
                                <th scope="col">Thứ</th>
                                <th scope="col">Môn học</th>
                                <th scope="col">Tiết</th>
                                <th scope="col">Lớp</th>
                                <th scope="col">Thời gian điểm danh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sr = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $sr; ?></th>
                                    <td><?php echo (int)$row['enrollment_no']; ?></td>
                                    <td><?php
                                        $efssql = "SELECT * FROM `students` WHERE `enrollment_no`=" . (int)$row['enrollment_no'];
                                        $efsresult = mysqli_query($conn, $efssql);
                                        $efsrow = mysqli_fetch_assoc($efsresult);
                                        echo ui_h((string)($efsrow['name'] ?? ''));
                                        ?></td>
                                    <td><?php echo ui_h((string)$row['date']); ?></td>
                                    <td><?php echo ui_h($daysVI[$row['day']] ?? $row['day']); ?></td>
                                    <td><?php
                                        $fssql = "SELECT * FROM `subjects` WHERE `subject_code`=" . (int)$row['subject_code'];
                                        $fsresult = mysqli_query($conn, $fssql);
                                        $fsrow = mysqli_fetch_assoc($fsresult);
                                        echo ui_h((string)($fsrow['name'] ?? ''));
                                        ?></td>
                                    <td><?php echo (int)$row['slot']; ?></td>
                                    <td><?php echo ui_h((string)$row['batch']); ?></td>
                                    <td><?php echo ui_h((string)$row['time']); ?></td>
                                </tr>
                            <?php
                                $sr++;
                            }
                            ?>
                        </tbody>
                    </table>
                <?php
                    }
                } else {
                ?>
                    <table class="table table-bordered table-striped text-center attendance-history-table" id="table">
                        <thead>
                            <tr>
                                <th scope="col">STT</th>
                                <th scope="col">Mã sinh viên</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Ngày</th>
                                <th scope="col">Thứ</th>
                                <th scope="col">Môn học</th>
                                <th scope="col">Tiết</th>
                                <th scope="col">Lớp</th>
                                <th scope="col">Thời gian điểm danh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sr = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $sr; ?></th>
                                    <td><?php echo (int)$row['enrollment_no']; ?></td>
                                    <td><?php
                                        $efssql = "SELECT * FROM `students` WHERE `enrollment_no`=" . (int)$row['enrollment_no'];
                                        $efsresult = mysqli_query($conn, $efssql);
                                        $efsrow = mysqli_fetch_assoc($efsresult);
                                        echo ui_h((string)($efsrow['name'] ?? ''));
                                        ?></td>
                                    <td><?php echo ui_h((string)$row['date']); ?></td>
                                    <td><?php echo ui_h($daysVI[$row['day']] ?? $row['day']); ?></td>
                                    <td><?php
                                        $fssql = "SELECT * FROM `subjects` WHERE `subject_code`=" . (int)$row['subject_code'];
                                        $fsresult = mysqli_query($conn, $fssql);
                                        $fsrow = mysqli_fetch_assoc($fsresult);
                                        echo ui_h((string)($fsrow['name'] ?? ''));
                                        ?></td>
                                    <td><?php echo (int)$row['slot']; ?></td>
                                    <td><?php echo ui_h((string)$row['batch']); ?></td>
                                    <td><?php echo ui_h((string)$row['time']); ?></td>
                                </tr>
                            <?php
                                $sr++;
                            }
                            ?>
                        </tbody>
                    </table>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính kết thúc -->

<?php
require('footer.php');
?>
