<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/ui.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'TEACHER') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

$teacherId = (int)$_SESSION['teacher_id'];
$teacher = db_row('SELECT id, name, branch FROM teachers WHERE id = ? LIMIT 1', 'i', [$teacherId]);
if (!$teacher) {
    http_response_code(403);
    exit('Không tìm thấy thông tin giảng viên.');
}

$yearRows = db_all(
    'SELECT DISTINCT t.academic_year
     FROM timetable t
     INNER JOIN subjects s ON s.subject_code = t.subject_code
     WHERE s.teacher_id = ?
     ORDER BY t.academic_year DESC',
    'i',
    [$teacherId]
);
$academicYears = array_values(array_filter(array_map(static function (array $row): string {
    return (string)($row['academic_year'] ?? '');
}, $yearRows), static function (string $year): bool {
    return (bool)preg_match('/^\d{4}-\d{2}$/', $year);
}));
if (!$academicYears) {
    $currentYear = (int)date('Y');
    $startYear = (int)date('n') >= 9 ? $currentYear : $currentYear - 1;
    $academicYears[] = $startYear . '-' . substr((string)($startYear + 1), -2);
}

$requestedYear = trim((string)($_GET['academic'] ?? ''));
$academicYear = in_array($requestedYear, $academicYears, true) ? $requestedYear : $academicYears[0];
$today = date('l');
$days = ui_days();
$todayClasses = db_all(
    'SELECT t.subject_code, t.slot, t.slotlabel, t.batch, t.semester, t.branch, s.name, s.abbreviation
     FROM timetable t
     INNER JOIN subjects s ON s.subject_code = t.subject_code
     WHERE t.academic_year = ? AND t.day = ? AND s.teacher_id = ? AND t.branch = ?
     ORDER BY t.slot ASC, t.batch ASC',
    'ssis',
    [$academicYear, $today, $teacherId, (string)$teacher['branch']]
);

$flash = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item active" aria-current="page">Thực hiện điểm danh</li>
        </ol>
    </nav>
</div>

<main class="container-fluid pt-4 px-4">
    <section class="bg-light rounded page-card">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h5 mb-1">Lớp học hôm nay</h1>
                <p class="text-muted mb-0">Hôm nay là <?= ui_h($days[$today] ?? $today) ?>, <?= date('d/m/Y') ?>.</p>
            </div>
            <a class="btn btn-outline-primary" href="my_timetable.php"><i class="fas fa-table me-2"></i>Xem toàn bộ thời khóa biểu</a>
        </div>

        <?php if ($flash !== ''): ?>
            <div class="alert alert-danger" role="alert">Liên kết mở phiên điểm danh không hợp lệ. Vui lòng chọn lớp từ danh sách bên dưới.</div>
        <?php endif; ?>

        <form class="attendance-filter border rounded-3 p-3 mb-4" method="get" action="take_attendance.php">
            <div>
                <label class="form-label" for="academicYear">Năm học</label>
                <select class="form-select" name="academic" id="academicYear">
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?= ui_h($year) ?>"<?= $academicYear === $year ? ' selected' : '' ?>><?= ui_h($year) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-grid">
                <button class="btn btn-success" type="submit"><i class="fas fa-calendar-day me-2"></i>Xem lớp hôm nay</button>
            </div>
        </form>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h6 mb-0">Các lớp của bạn trong ngày</h2>
            <span class="badge bg-primary rounded-pill"><?= count($todayClasses) ?> lớp</span>
        </div>

        <?php if (!$todayClasses): ?>
            <div class="alert alert-secondary mb-0">Không có lớp nào được phân công vào <?= ui_h($days[$today] ?? $today) ?> của năm học <?= ui_h($academicYear) ?>.</div>
        <?php else: ?>
            <div class="class-list">
                <?php foreach ($todayClasses as $class): ?>
                    <?php
                    $slotText = trim((string)$class['slotlabel']) !== ''
                        ? (string)$class['slotlabel']
                        : 'Tiết ' . (int)$class['slot'];
                    $attendanceUrl = 'take_attend.php?' . http_build_query([
                        'subject_code' => (int)$class['subject_code'],
                        'slot' => (int)$class['slot'],
                        'batch' => (string)$class['batch'],
                        'day' => $today,
                        'semester' => (int)$class['semester'],
                        'slotlabel' => $slotText,
                        'academic' => $academicYear,
                    ]);
                    ?>
                    <article class="class-card">
                        <div>
                            <p class="class-card__slot mb-2"><i class="far fa-clock me-2"></i><?= ui_h($slotText) ?></p>
                            <h3 class="class-card__title"><?= ui_h($class['name']) ?></h3>
                            <?php if (trim((string)$class['abbreviation']) !== ''): ?>
                                <p class="text-muted small mb-0 mt-1"><?= ui_h($class['abbreviation']) ?></p>
                            <?php endif; ?>
                        </div>
                        <dl class="class-card__meta">
                            <div><dt>Lớp</dt><dd><?= ui_h($class['batch']) ?></dd></div>
                            <div><dt>Học kỳ</dt><dd><?= (int)$class['semester'] ?></dd></div>
                            <div><dt>Tiết</dt><dd><?= (int)$class['slot'] ?></dd></div>
                        </dl>
                        <a class="btn btn-primary" href="<?= ui_h($attendanceUrl) ?>"><i class="fas fa-qrcode me-2"></i>Mở điểm danh</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/footer.php'; ?>
