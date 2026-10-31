<?php
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/ui.php';
security_start_session();
if (isset($_SESSION['logged']) && $_SESSION['logged'] == true) {
} else {
    header("location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Hệ thống điểm danh QR Code</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.3/css/dataTables.dataTables.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/3.1.1/css/buttons.dataTables.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.1/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.1/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.1/js/buttons.colVis.min.js"></script>
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div class="app-shell position-relative bg-white d-flex p-0">
        <!-- Vòng quay tải trang Bắt đầu -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Đang tải...</span>
            </div>
        </div>
        <!-- Vòng quay tải trang Kết thúc -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var spinner = document.getElementById('spinner');
                if (spinner) spinner.classList.remove('show');
            });
        </script>
        <?php
        if ($_SESSION['usertype'] == "ADMIN") {
        ?>
            <!-- Sidebar Quản trị viên Bắt đầu -->
            <div class="sidebar pe-4 pb-3">
                <nav class="navbar bg-light navbar-light">
                    <a href="./" class="navbar-brand school-brand mx-4 mb-3">
                        <img class="school-brand__logo" src="img/hus-logo.jpg" alt="HUS - Trường Đại học Khoa học Tự nhiên">
                    </a>
                    <div class="d-flex align-items-center ms-4 mb-4">
                        <div class="position-relative">
                            <img class="rounded-circle" src="img/user.jpg" alt="" style="width: 40px; height: 40px;">
                            <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0"><?php echo $_SESSION['username']; ?></h6>
                            <span><?php echo ui_role_label($_SESSION['usertype']); ?></span>
                        </div>
                    </div>
                    <div class="navbar-nav w-100">
                        <a href="./" class="nav-item nav-link"><i class="fa fa-tachometer-alt me-2"></i>Bảng điều khiển</a>
                        <a href="view_admin_attend.php" class="nav-item nav-link"><i class="fas fa-clipboard-check me-2"></i>Xem điểm danh</a>
                        <a href="manual_attend.php" class="nav-item nav-link"><i class="fas fa-clipboard-check me-2"></i>Điểm danh</a>
                        <a href="stud_details.php" class="nav-item nav-link"><i class="fas fa-user-graduate me-2"></i>Danh sách sinh viên</a>
                        <a href="teacher_details.php" class="nav-item nav-link"><i class="fas fa-chalkboard-teacher me-2"></i>Danh sách giáo viên</a>
                        <a href="subject_details.php" class="nav-item nav-link"><i class="fas fa-book me-2"></i>Danh sách môn học</a>
                        <a href="timetable.php" class="nav-item nav-link"><i class="fas fa-table me-2"></i>Thời khóa biểu</a>
                        <a href="settings.php" class="nav-item nav-link"><i class="fas fa-cog me-2"></i>Cài đặt</a>
                        <a href="passkey_admin.php" class="nav-item nav-link"><i class="fas fa-mobile-alt me-2"></i>Thiết bị</a>
                    </div>
                </nav>
            </div>
            <!-- Sidebar Quản trị viên Kết thúc -->
        <?php
        } else if ($_SESSION['usertype'] == "STUDENT") {
        ?>
            <!-- Sidebar Sinh viên Bắt đầu -->
            <div class="sidebar pe-4 pb-3">
                <nav class="navbar bg-light navbar-light">
                    <a href="./" class="navbar-brand school-brand mx-4 mb-3">
                        <img class="school-brand__logo" src="img/hus-logo.jpg" alt="HUS - Trường Đại học Khoa học Tự nhiên">
                    </a>
                    <div class="d-flex align-items-center ms-4 mb-4">
                        <div class="position-relative">
                            <img class="rounded-circle" src="img/user.jpg" alt="" style="width: 40px; height: 40px;">
                            <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0"><?php echo $_SESSION['username']; ?></h6>
                            <span><?php echo ui_role_label($_SESSION['usertype']); ?></span>
                        </div>
                    </div>
                    <div class="navbar-nav w-100">
                        <a href="./" class="nav-item nav-link"><i class="fa fa-tachometer-alt me-2"></i>Bảng điều khiển</a>
                        <a href="give_attend.php" class="nav-item nav-link"><i class="far fa-calendar-check me-2"></i>Điểm danh</a>
                        <a href="view_stud_attend.php" class="nav-item nav-link"><i class="fas fa-clipboard-check me-2"></i>Xem điểm danh</a>
                        <a href="my_timetable.php" class="nav-item nav-link"><i class="fas fa-table me-2"></i>Thời khóa biểu của tôi</a>
                        <a href="face_enrollment.php" class="nav-item nav-link"><i class="fas fa-user-check me-2"></i>Đăng ký khuôn mặt</a>
                        <a href="profile_stud.php" class="nav-item nav-link"><i class="fas fa-user me-2"></i>Hồ sơ cá nhân</a>
                    </div>
                </nav>
            </div>
            <!-- Sidebar Sinh viên Kết thúc -->
        <?php
        } else {
        ?>
            <!-- Sidebar Giáo viên Bắt đầu -->
            <div class="sidebar pe-4 pb-3">
                <nav class="navbar bg-light navbar-light">
                    <a href="./" class="navbar-brand school-brand mx-4 mb-3">
                        <img class="school-brand__logo" src="img/hus-logo.jpg" alt="HUS - Trường Đại học Khoa học Tự nhiên">
                    </a>
                    <div class="d-flex align-items-center ms-4 mb-4">
                        <div class="position-relative">
                            <img class="rounded-circle" src="img/user.jpg" alt="" style="width: 40px; height: 40px;">
                            <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0"><?php echo $_SESSION['username']; ?></h6>
                            <span><?php echo ui_role_label($_SESSION['usertype']); ?></span>
                        </div>
                    </div>
                    <div class="navbar-nav w-100">
                        <a href="./" class="nav-item nav-link"><i class="fa fa-tachometer-alt me-2"></i>Bảng điều khiển</a>
                        <a href="take_attendance.php" class="nav-item nav-link"><i class="far fa-calendar-check me-2"></i>Thực hiện điểm danh</a>
                        <a href="my_timetable.php" class="nav-item nav-link"><i class="fas fa-table me-2"></i>Thời khóa biểu của tôi</a>
                        <a href="view_teacher_attend.php" class="nav-item nav-link"><i class="fas fa-clipboard-check me-2"></i>Xem điểm danh</a>
                    </div>
                </nav>
            </div>
            <!-- Sidebar Giáo viên Kết thúc -->
        <?php
        }
        ?>

        <!-- Nội dung Bắt đầu -->
        <div class="content">
            <!-- Thanh điều hướng Bắt đầu -->
            <nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
                <a href="./" class="navbar-brand topbar-school-brand d-flex d-lg-none me-3">
                    <img src="img/hus-logo.jpg" alt="HUS">
                </a>
                <a href="#" class="sidebar-toggler flex-shrink-0">
                    <i class="fa fa-bars"></i>
                </a>
                <div class="navbar-nav align-items-center ms-auto">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <img class="rounded-circle me-lg-2" src="img/user.jpg" alt="" style="width: 40px; height: 40px;">
                            <span class="d-none d-lg-inline-flex"><?php echo $_SESSION['username']; ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                            <!-- <a href="#" class="dropdown-item">Hồ sơ của tôi</a> -->
                            <!-- <a href="#" class="dropdown-item">Cài đặt</a> -->
                            <a href="logout.php" class="dropdown-item">Đăng xuất</a>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Thanh điều hướng Kết thúc -->
