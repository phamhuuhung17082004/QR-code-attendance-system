<?php
require 'conn.php';
require_once __DIR__ . '/lib/db.php';
require 'header.php';

$role = (string)($_SESSION['usertype'] ?? 'ADMIN');
$displayName = (string)($_SESSION['username'] ?? '');
$profileRows = [];
$stats = [];
$quickActions = [];

if ($role === 'TEACHER' && isset($_SESSION['teacher_id'])) {
    $teacher = db_row('SELECT id, name, education, designation, branch FROM teachers WHERE id = ? LIMIT 1', 'i', [(int)$_SESSION['teacher_id']]);
    if ($teacher) {
        $displayName = (string)$teacher['name'];
        $profileRows = [
            ['Mã giảng viên', $teacher['id']],
            ['Khoa/ngành', ui_branch_label((string)$teacher['branch'])],
            ['Chức danh', ui_designation_label((string)$teacher['designation'])],
            ['Trình độ', ui_education_label((string)$teacher['education'])],
        ];
    }
    $subjectTotal = (int)(db_val('SELECT COUNT(*) FROM subjects WHERE teacher_id = ?', 'i', [(int)$_SESSION['teacher_id']]) ?? 0);
    $stats = [
        ['fa-book-open', 'Môn phụ trách', $subjectTotal, 'my_timetable.php'],
        ['fa-calendar-alt', 'Thời khóa biểu', 'Xem lịch', 'my_timetable.php'],
        ['fa-clipboard-check', 'Lịch sử điểm danh', 'Tra cứu', 'view_teacher_attend.php'],
    ];
    $quickActions = [
        ['fa-qrcode', 'Mở điểm danh', 'Tạo mã QR cho buổi học', 'take_attendance.php', 'primary'],
        ['fa-calendar-week', 'Xem thời khóa biểu', 'Kiểm tra các lớp sắp dạy', 'my_timetable.php', 'soft'],
        ['fa-clipboard-check', 'Xem kết quả', 'Theo dõi sinh viên đã điểm danh', 'view_teacher_attend.php', 'soft'],
    ];
} elseif ($role === 'STUDENT' && isset($_SESSION['enrollment_no'])) {
    $student = db_row('SELECT enrollment_no, name, semester, branch, roll_no, batch FROM students WHERE enrollment_no = ? LIMIT 1', 'i', [(int)$_SESSION['enrollment_no']]);
    if ($student) {
        $displayName = (string)$student['name'];
        $profileRows = [
            ['Mã sinh viên', $student['enrollment_no']],
            ['Khoa/ngành', ui_branch_label((string)$student['branch'])],
            ['Học kỳ', $student['semester']],
            ['Nhóm', $student['batch']],
        ];
    }
    $attendanceTotal = (int)(db_val('SELECT COUNT(*) FROM attendance WHERE enrollment_no = ?', 'i', [(int)$_SESSION['enrollment_no']]) ?? 0);
    $stats = [
        ['fa-clipboard-check', 'Lần đã điểm danh', $attendanceTotal, 'view_stud_attend.php'],
        ['fa-calendar-alt', 'Thời khóa biểu', 'Xem lịch', 'my_timetable.php'],
        ['fa-user-check', 'Khuôn mặt', 'Đăng ký', 'face_enrollment.php'],
    ];
    $quickActions = [
        ['fa-qrcode', 'Điểm danh ngay', 'Quét mã QR của giảng viên', 'give_attend.php', 'primary'],
        ['fa-calendar-week', 'Xem thời khóa biểu', 'Xem lớp học của bạn', 'my_timetable.php', 'soft'],
        ['fa-user-check', 'Đăng ký khuôn mặt', 'Hoàn thiện xác minh khuôn mặt', 'face_enrollment.php', 'soft'],
    ];
} else {
    $studentTotal = (int)(db_val('SELECT COUNT(*) FROM students') ?? 0);
    $teacherTotal = (int)(db_val('SELECT COUNT(*) FROM teachers') ?? 0);
    $subjectTotal = (int)(db_val('SELECT COUNT(*) FROM subjects') ?? 0);
    $profileRows = [
        ['Vai trò', ui_role_label($role)],
        ['Quyền truy cập', 'Quản lý hệ thống'],
        ['Trạng thái', 'Đang hoạt động'],
    ];
    $stats = [
        ['fa-user-graduate', 'Sinh viên', $studentTotal, 'stud_details.php'],
        ['fa-chalkboard-teacher', 'Giảng viên', $teacherTotal, 'teacher_details.php'],
        ['fa-book-open', 'Môn học', $subjectTotal, 'subject_details.php'],
    ];
    $quickActions = [
        ['fa-clipboard-check', 'Điểm danh thủ công', 'Ghi nhận hoặc điều chỉnh điểm danh', 'manual_attend.php', 'primary'],
        ['fa-calendar-week', 'Quản lý thời khóa biểu', 'Tạo và chỉnh sửa lịch học', 'timetable.php', 'soft'],
        ['fa-chart-line', 'Xem báo cáo điểm danh', 'Tra cứu lịch sử toàn hệ thống', 'view_admin_attend.php', 'soft'],
    ];
}

$roleLabel = ui_role_label($role);
?>

<main class="dashboard-page">
    <nav class="dashboard-breadcrumb" aria-label="breadcrumb">
        <span><i class="fa fa-home"></i> Trang chủ</span>
        <span aria-hidden="true">/</span>
        <strong>Bảng điều khiển</strong>
    </nav>

    <section class="dashboard-hero" aria-labelledby="dashboard-title">
        <div class="dashboard-hero__copy">
            <span class="dashboard-hero__eyebrow"><i class="fas fa-university"></i> Hệ thống điểm danh QR</span>
            <h1 id="dashboard-title">Chào <?= $role === 'TEACHER' ? 'thầy/cô' : 'bạn' ?>, <?= ui_h($displayName) ?></h1>
            <p>
                <?= $role === 'ADMIN'
                    ? 'Theo dõi dữ liệu lớp học và quản lý hoạt động điểm danh tại một nơi.'
                    : ($role === 'TEACHER'
                        ? 'Bắt đầu buổi học, mở mã QR và theo dõi kết quả điểm danh thật dễ dàng.'
                        : 'Xem lịch học, quét mã QR và theo dõi quá trình điểm danh của bạn.'); ?>
            </p>
            <div class="dashboard-hero__actions">
                <a class="btn btn-light" href="<?= ui_h($quickActions[0][3]) ?>"><i class="fas <?= ui_h($quickActions[0][0]) ?> me-2"></i><?= ui_h($quickActions[0][1]) ?></a>
                <a class="btn btn-hero-outline" href="<?= ui_h($quickActions[1][3]) ?>">Khám phá chức năng <i class="fa fa-arrow-right ms-2"></i></a>
            </div>
        </div>
        <div class="dashboard-hero__visual" aria-hidden="true">
            <div class="dashboard-orbit dashboard-orbit--one"></div>
            <div class="dashboard-orbit dashboard-orbit--two"></div>
            <div class="dashboard-hero__qr"><i class="fas fa-qrcode"></i></div>
            <div class="dashboard-hero__badge dashboard-hero__badge--top"><i class="fas fa-shield-alt"></i><span>Xác thực an toàn</span></div>
            <div class="dashboard-hero__badge dashboard-hero__badge--bottom"><i class="fas fa-graduation-cap"></i><span><?= ui_h($roleLabel) ?></span></div>
        </div>
    </section>

    <section class="dashboard-stat-grid" aria-label="Tổng quan nhanh">
        <?php foreach ($stats as $stat): ?>
            <a class="dashboard-stat" href="<?= ui_h($stat[3]) ?>">
                <span class="dashboard-stat__icon"><i class="fas <?= ui_h($stat[0]) ?>"></i></span>
                <span>
                    <small><?= ui_h($stat[1]) ?></small>
                    <strong><?= ui_h($stat[2]) ?></strong>
                </span>
                <i class="fa fa-arrow-right dashboard-stat__arrow" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-bottom-grid">
        <article class="dashboard-card dashboard-profile-card">
            <div class="dashboard-card__heading">
                <div>
                    <span class="dashboard-card__eyebrow">TÀI KHOẢN</span>
                    <h2>Thông tin của bạn</h2>
                </div>
                <span class="dashboard-avatar"><i class="fas fa-user"></i></span>
            </div>
            <dl class="dashboard-profile-list">
                <?php foreach ($profileRows as $profileRow): ?>
                    <div>
                        <dt><?= ui_h($profileRow[0]) ?></dt>
                        <dd><?= ui_h($profileRow[1]) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </article>

        <article class="dashboard-card dashboard-actions-card">
            <div class="dashboard-card__heading">
                <div>
                    <span class="dashboard-card__eyebrow">THAO TÁC NHANH</span>
                    <h2>Bạn muốn làm gì?</h2>
                </div>
            </div>
            <div class="dashboard-action-list">
                <?php foreach ($quickActions as $action): ?>
                    <a class="dashboard-action dashboard-action--<?= ui_h($action[4]) ?>" href="<?= ui_h($action[3]) ?>">
                        <span class="dashboard-action__icon"><i class="fas <?= ui_h($action[0]) ?>"></i></span>
                        <span><strong><?= ui_h($action[1]) ?></strong><small><?= ui_h($action[2]) ?></small></span>
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>

<?php require 'footer.php'; ?>
