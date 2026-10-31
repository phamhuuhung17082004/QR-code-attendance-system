<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/db.php';

$role = $_SESSION['usertype'] ?? '';
if (!in_array($role, ['STUDENT', 'TEACHER'], true)) {
    header('Location: ./');
    exit();
}

$years = ui_academic_years();
$profile = null;
if ($role === 'STUDENT') {
    $profile = db_row('SELECT enrollment_no, name, branch, semester, batch FROM students WHERE enrollment_no = ? LIMIT 1', 'i', [(int)$_SESSION['enrollment_no']]);
    if (!$profile) {
        exit('Không tìm thấy hồ sơ sinh viên.');
    }
    $yearRows = db_all('SELECT DISTINCT academic_year FROM timetable WHERE branch = ? AND semester = ? AND batch = ?', 'sis', [$profile['branch'], (int)$profile['semester'], $profile['batch']]);
} else {
    $profile = db_row('SELECT id, name, branch FROM teachers WHERE id = ? LIMIT 1', 'i', [(int)$_SESSION['teacher_id']]);
    if (!$profile) {
        exit('Không tìm thấy hồ sơ giảng viên.');
    }
    $yearRows = db_all('SELECT DISTINCT t.academic_year FROM timetable t INNER JOIN subjects s ON s.subject_code = t.subject_code WHERE s.teacher_id = ?', 'i', [(int)$profile['id']]);
}
foreach ($yearRows as $row) {
    $years[] = $row['academic_year'];
}
$years = array_values(array_unique($years));
rsort($years, SORT_NATURAL);
$academicYear = trim((string)($_GET['academic'] ?? ($years[0] ?? '')));

if ($role === 'STUDENT') {
    $rows = db_all(
        'SELECT t.day, t.slot, t.slotlabel, t.subject_code, s.name AS subject_name
         FROM timetable t LEFT JOIN subjects s ON s.subject_code = t.subject_code
         WHERE t.academic_year = ? AND t.branch = ? AND t.semester = ? AND t.batch = ?
         ORDER BY FIELD(t.day, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"), t.slot',
        'ssis', [$academicYear, $profile['branch'], (int)$profile['semester'], $profile['batch']]
    );
} else {
    $rows = db_all(
        'SELECT t.day, t.slot, t.slotlabel, t.batch, t.semester, t.branch, t.subject_code, s.name AS subject_name
         FROM timetable t INNER JOIN subjects s ON s.subject_code = t.subject_code
         WHERE t.academic_year = ? AND s.teacher_id = ?
         ORDER BY FIELD(t.day, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"), t.slot, t.batch',
        'si', [$academicYear, (int)$profile['id']]
    );
}
$days = ui_days();
?>
<div class="container pt-3 px-4 m-0"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee"><li class="breadcrumb-item">Trang chủ</li><li class="breadcrumb-item active">Thời khóa biểu của tôi</li></ol></nav></div>
<div class="container-fluid pt-4 px-4"><div class="row bg-light rounded mx-0"><div class="col-12"><div class="p-4">
    <h6>Thời khóa biểu của tôi</h6>
    <p class="mb-3"><?php if ($role === 'STUDENT'): ?>Sinh viên: <strong><?= ui_h($profile['name']) ?></strong> · <?= ui_h(ui_branch_label($profile['branch'])) ?> · Học kỳ <?= (int)$profile['semester'] ?> · Lớp <?= ui_h($profile['batch']) ?><?php else: ?>Giảng viên: <strong><?= ui_h($profile['name']) ?></strong> · <?= ui_h(ui_branch_label($profile['branch'])) ?><?php endif; ?></p>
    <form class="row g-2 align-items-end mb-4" method="get"><div class="col-sm-4"><label class="form-label">Năm học</label><select class="form-select" name="academic" onchange="this.form.submit()"><?php foreach ($years as $year): ?><option value="<?= ui_h($year) ?>" <?= $academicYear === $year ? 'selected' : '' ?>><?= ui_h($year) ?></option><?php endforeach; ?></select></div><div class="col-sm-2"><button class="btn btn-success w-100" type="submit">Xem lịch</button></div></form>
    <?php if (!$rows): ?>
        <div class="alert alert-warning">Chưa có lịch học trong năm học đã chọn.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Thứ</th><th>Tiết</th><th>Môn học</th><?php if ($role === 'TEACHER'): ?><th>Lớp</th><th>Học kỳ</th><th>Ngành</th><?php endif; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= ui_h($days[$row['day']] ?? $row['day']) ?></td><td><?= ui_h($row['slotlabel'] ?: (ui_slot_labels()[(int)$row['slot']] ?? 'Tiết ' . $row['slot'])) ?></td><td><?= ui_h($row['subject_name'] ?: 'Môn #' . $row['subject_code']) ?></td><?php if ($role === 'TEACHER'): ?><td><?= ui_h($row['batch']) ?></td><td><?= (int)$row['semester'] ?></td><td><?= ui_h(ui_branch_label($row['branch'])) ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</div></div></div></div>
<?php require_once __DIR__ . '/footer.php'; ?>
