<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'TEACHER') {
    session_destroy();
    header("location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_sql = "SELECT * FROM teachers WHERE `id`=$teacher_id";
$teacher_res = mysqli_query($conn, $teacher_sql);
$teacher_row = mysqli_fetch_assoc($teacher_res);
$branch = $teacher_row['branch'];
$subArrstr = "";

$ssql = "SELECT * FROM `subjects` WHERE teacher_id=$teacher_id";
$sresult = mysqli_query($conn, $ssql);
while ($row = mysqli_fetch_assoc($sresult)) {
    if (empty($subArrstr)) {
        $subArrstr = $row['subject_code'];
    } else {
        $subArrstr = $subArrstr . "," . $row['subject_code'];
    }
}

$sql = "SELECT * FROM `attendance` WHERE subject_code IN ($subArrstr) AND `branch`='$branch' ORDER BY id DESC";
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
                <h6 class="mb-4">Xem điểm danh</h6>
                <form class="attendance-report-filter mb-4" action="view_teacher_attend.php" method="get">
                    <div class="attendance-report-filter__field">
                        <label class="form-label" for="filterStartDate">Từ ngày</label>
                        <input type="date" name="startdate" id="filterStartDate" class="form-control" value="<?= htmlspecialchars($_GET['startdate'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="attendance-report-filter__field">
                        <label class="form-label" for="filterEndDate">Đến ngày</label>
                        <input type="date" name="enddate" id="filterEndDate" class="form-control" value="<?= htmlspecialchars($_GET['enddate'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="attendance-report-filter__field attendance-report-filter__field--subject">
                        <label class="form-label" for="filterSubject">Môn học</label>
                        <select class="form-select" name="subject" id="filterSubject" required>
                            <option value="">Chọn môn học</option>
                            <?php
                            $sssql = "SELECT * FROM `subjects` WHERE teacher_id=$teacher_id";
                            $ssresult = mysqli_query($conn, $sssql);
                            while ($erow = mysqli_fetch_array($ssresult)) {
                                $selected = (string)($erow['subject_code']) === (string)($_GET['subject'] ?? '') ? ' selected' : '';
                                echo '<option value="' . (int)$erow['subject_code'] . '"' . $selected . '>' . ui_h((string)$erow['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="attendance-report-filter__field attendance-report-filter__field--batch">
                        <label class="form-label" for="filterBatch">Nhóm</label>
                        <select class="form-select" name="batch" id="filterBatch" required>
                            <option value="">Chọn nhóm</option>
                            <?php foreach (['A1', 'A2', 'A3', 'A4', 'A5'] as $batchOption): ?>
                                <option value="<?= ui_h((string)$batchOption) ?>"<?= ($batchOption === ($_GET['batch'] ?? '')) ? ' selected' : '' ?>><?= ui_h((string)$batchOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="attendance-report-filter__action">
                        <button type="submit" class="btn btn-success"><i class="fas fa-search me-2"></i>Tìm kiếm</button>
                    </div>
                </form>

                <?php
                if (isset($_GET['startdate']) && isset($_GET['enddate']) && isset($_GET['subject']) && isset($_GET['batch'])) {
                    $sdateString = $_GET['startdate'];
                    $edateString = $_GET['enddate'];
                    $subject_code = $_GET['subject'];
                    $batch = $_GET['batch'];

                    $sql = "SELECT * FROM attendance WHERE STR_TO_DATE(`date`, '%Y-%m-%d') BETWEEN STR_TO_DATE('$sdateString', '%Y-%m-%d') AND STR_TO_DATE('$edateString', '%Y-%m-%d') AND `subject_code`=$subject_code AND `batch`='$batch' AND `branch`='" . $teacher_row['branch'] . "' ORDER BY id DESC";
                    $result = mysqli_query($conn, $sql);
                ?>
                    <h6>Kết quả lọc: Từ ngày <?php echo ui_h((string)$_GET['startdate']); ?> đến ngày <?php echo ui_h((string)$_GET['enddate']); ?></h6>
                    <div class="table-responsive">
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
                                    <th scope="col">Nhóm</th>
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
                    </div>
                <?php
                } else {
                ?>
                    <div class="table-responsive">
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
                                    <th scope="col">Nhóm</th>
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
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính Kết thúc -->

<?php
require('footer.php');
?>
