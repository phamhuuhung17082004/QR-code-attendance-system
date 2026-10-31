<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    session_destroy();
    header("location: login.php");
    exit();
}
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

// Nếu đang từ tháng 9 trở đi thì năm học là năm hiện tại - năm sau
// Nếu trước tháng 9 thì năm học là năm trước - năm hiện tại
if ($currentMonth >= 9) {
    $academicYearStart = $currentYear;
} else {
    $academicYearStart = $currentYear - 1;
}
$academicYears = [];
for ($i = -1; $i <= 1; $i++) {
    $start = $academicYearStart + $i;
    $end = $start + 1;
    $endShort = substr($end, 2, 2); // Lấy 2 số cuối của năm kết thúc
    $academicYears[] = $start . '-' . $endShort;
}
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
                <h6 class="mb-4">Điểm danh thủ công</h6>
                <form class="row gy-2 gx-3 align-items-center border p-2" action="manual_attend.php" method="get">
                    <div class="col-auto">
                        <p>Xem thời khóa biểu</p>
                    </div>

                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Năm học</label>
                        <select class="form-select" name="academic" id="autoSizingSelect">
                            <option value="">-- Chọn năm học --</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Học kỳ</label>
                        <select class="form-select" name="semester" id="autoSizingSelect">
                            <option value="">-- Chọn học kỳ --</option>
                            <option value="1">Học kỳ 1</option>
                            <option value="2">Học kỳ 2</option>
                            <option value="3">Học kỳ 3</option>
                            <option value="4">Học kỳ 4</option>
                            <option value="5">Học kỳ 5</option>
                            <option value="6">Học kỳ 6</option>
                            <option value="7">Học kỳ 7</option>
                            <option value="8">Học kỳ 8</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Ngành</label>
                        <select class="form-select" name="branch" id="autoSizingSelect">
                            <option value="">-- Chọn ngành --</option>
                            <option value="Computer Engineering">Kỹ thuật Máy tính</option>
                            <option value="Mechanical Engineering">Kỹ thuật Cơ khí</option>
                            <option value="Electrical Engineering">Kỹ thuật Điện</option>
                            <option value="Civil Engineering">Kỹ thuật Xây dựng</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingSelect">Lớp</label>
                        <select class="form-select" name="batch" id="autoSizingSelect">
                            <option value="">-- Chọn lớp --</option>
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="A3">A3</option>
                            <option value="A4">A4</option>
                            <option value="A5">A5</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-success">Xem thời khóa biểu</button>
                    </div>
                </form>

                <?php
                if (isset($_GET['batch']) && isset($_GET['semester']) && isset($_GET['academic']) && isset($_GET['branch'])) {
                ?>

                    <h6 class="mb-3 text-center mt-3 text-danger">
                        Chi tiết thời khóa biểu: Năm học - [<?php echo $_GET['academic']; ?>],
                        Ngành - [<?php echo ui_h(ui_branch_label($_GET['branch'])); ?>],
                        Lớp - [<?php echo $_GET['batch']; ?>],
                        Học kỳ - [<?php echo $_GET['semester']; ?>]
                    </h6>

                    <?php
                    $semester = mysqli_escape_string($conn, $_GET['semester']);
                    $batch = mysqli_escape_string($conn, $_GET['batch']);
                    $academic = mysqli_escape_string($conn, $_GET['academic']);
                    $branch = mysqli_escape_string($conn, $_GET['branch']);
                    $sql = "SELECT * FROM `timetable` WHERE `academic_year`='$academic' AND  `branch`='$branch' AND `semester`='$semester' AND `batch`='$batch'";
                    $sqlslot = "SELECT DISTINCT `slot`,`slotlabel` FROM `timetable` WHERE `academic_year`='$academic' AND  `branch`='$branch' AND `semester`='$semester' AND `batch`='$batch'";
                    $result1 = mysqli_query($conn, $sqlslot);
                    $result2 = mysqli_query($conn, $sql);

                    $slots = [];
                    while ($row = mysqli_fetch_assoc($result1)) {
                        $slots[$row['slot']] = $row['slotlabel'];
                    }

                    $timetable = [];
                    while ($row = mysqli_fetch_assoc($result2)) {
                        $day = $row['day'];
                        $slot = $row['slot'];
                        if (!isset($timetable[$day])) {
                            $timetable[$day] = [];
                        }
                        $timetable[$day][$slot] = $row['subject_code'];
                    }

                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">Thứ</th>
                                    <?php foreach ($slots as $slot => $slotLabel) : ?>
                                        <th scope="col"><?php echo $slotLabel; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Ánh xạ tên ngày tiếng Anh sang tiếng Việt
                                $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $daysVI = [
                                    'Monday'    => 'Thứ Hai',
                                    'Tuesday'   => 'Thứ Ba',
                                    'Wednesday' => 'Thứ Tư',
                                    'Thursday'  => 'Thứ Năm',
                                    'Friday'    => 'Thứ Sáu',
                                    'Saturday'  => 'Thứ Bảy',
                                ];
                                foreach ($daysOfWeek as $day) :
                                    $dayData = isset($timetable[$day]) ? $timetable[$day] : [];
                                ?>
                                    <tr>
                                        <td><?php echo $daysVI[$day]; ?></td>
                                        <?php foreach ($slots as $slot => $slotLabel) : ?>
                                            <td>
                                                <?php
                                                $subjectCode = isset($dayData[$slot]) ? $dayData[$slot] : '';
                                                if ($subjectCode) {
                                                    $subjectSQL = "SELECT `name` FROM `subjects` WHERE `subject_code`='$subjectCode'";
                                                    $subjectRes = mysqli_query($conn, $subjectSQL);
                                                    $subjectRow = mysqli_fetch_assoc($subjectRes);
                                                    echo $subjectRow['name'];
                                                    echo '<a href="give_manual_attend.php?subject_code=' . $subjectCode . '&slot=' . $slot . '&batch=' . $batch . '&semester=' . $semester . '&branch=' . $branch . '&day=' . $day . '" title="Điểm danh"><i class="fas fa-clipboard-check ms-2"></i></a>';
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
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
<!-- Nội dung chính kết thúc -->




<?php
require('footer.php');
?>
