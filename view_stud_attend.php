<?php
require('header.php');
require('conn.php');


if ($_SESSION['usertype'] != 'STUDENT') {
    session_destroy();
    header("location: login.php");
    exit();
}

$enrollment_no = $_SESSION['enrollment_no'];
$sql = "SELECT * FROM `attendance` WHERE `enrollment_no`= $enrollment_no ORDER BY id DESC";
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

                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center attendance-history-table" id="table">
                        <thead>
                            <tr>
                                <th scope="col">STT</th>
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
                                    <td><?php echo $row['date']; ?></td>
                                    <td><?php echo isset($daysVI[$row['day']]) ? $daysVI[$row['day']] : $row['day']; ?></td>
                                    <td><?php
                                        $fssql = "SELECT * FROM `subjects` WHERE `subject_code`=" . $row['subject_code'];
                                        $fsresult = mysqli_query($conn, $fssql);
                                        $fsrow = mysqli_fetch_assoc($fsresult);
                                        echo $fsrow['name'];
                                        ?></td>
                                    <td><?php echo $row['slot']; ?></td>
                                    <td><?php echo $row['batch']; ?></td>
                                    <td><?php echo $row['time']; ?></td>
                                </tr>
                            <?php
                                $sr++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính kết thúc -->



<?php
require('footer.php');
?>
