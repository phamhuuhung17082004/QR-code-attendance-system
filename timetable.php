<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/db.php';

if (($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header('Location: login.php');
    exit();
}

$branches = [
    'Computer Engineering' => 'Kỹ thuật máy tính',
    'Civil Engineering' => 'Kỹ thuật xây dựng',
    'Mechanical Engineering' => 'Kỹ thuật cơ khí',
    'Electrical Engineering' => 'Kỹ thuật điện',
];
$academicYears = ui_academic_years();
foreach (db_all('SELECT DISTINCT academic_year FROM timetable WHERE academic_year <> ""') as $row) {
    $academicYears[] = $row['academic_year'];
}
$academicYears = array_values(array_unique($academicYears));
rsort($academicYears, SORT_NATURAL);

$batches = [];
foreach (db_all('SELECT DISTINCT batch FROM students WHERE batch <> "" UNION SELECT DISTINCT batch FROM timetable WHERE batch <> ""') as $row) {
    $batches[] = $row['batch'];
}
sort($batches, SORT_NATURAL | SORT_FLAG_CASE);
$subjects = db_all('SELECT subject_code, name, semester, branch FROM subjects ORDER BY branch, semester, name');

$selectedAcademic = trim((string)($_GET['academic'] ?? ''));
$selectedBranch = trim((string)($_GET['branch'] ?? ''));
$selectedSemester = filter_var($_GET['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]]);
$selectedBatch = trim((string)($_GET['batch'] ?? ''));
$showSchedule = $selectedAcademic !== '' && isset($branches[$selectedBranch]) && $selectedSemester && $selectedBatch !== '';
$entries = [];
if ($showSchedule) {
    $entries = db_all(
        'SELECT t.day, t.slot, t.slotlabel, t.subject_code, s.name AS subject_name
         FROM timetable t
         LEFT JOIN subjects s ON s.subject_code = t.subject_code
         WHERE t.academic_year = ? AND t.branch = ? AND t.semester = ? AND t.batch = ?
         ORDER BY t.slot, FIELD(t.day, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday")',
        'ssis',
        [$selectedAcademic, $selectedBranch, (int)$selectedSemester, $selectedBatch]
    );
}
$grid = [];
$usedSlots = [];
foreach ($entries as $entry) {
    $grid[$entry['day']][(int)$entry['slot']] = $entry;
    $usedSlots[(int)$entry['slot']] = $entry['slotlabel'] ?: (ui_slot_labels()[(int)$entry['slot']] ?? 'Tiết ' . $entry['slot']);
}
ksort($usedSlots, SORT_NUMERIC);
$days = ui_days();
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee"><li class="breadcrumb-item">Trang chủ</li><li class="breadcrumb-item active">Thời khóa biểu</li></ol></nav>
</div>

<div class="container-fluid pt-4 px-4">
    <div class="text-center w-100">
        <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
        <button type="button" class="btn btn-outline-primary m-2" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Tạo thời khóa biểu</button>
        <button type="button" class="btn btn-outline-danger m-2" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fas fa-trash me-2"></i>Xóa thời khóa biểu</button>
    </div>

    <div class="row bg-light rounded mx-0"><div class="col-12"><div class="p-4">
        <h6 class="mb-3">Xem thời khóa biểu</h6>
        <form class="row gy-2 gx-3 align-items-end border rounded p-3" method="get" action="timetable.php">
            <div class="col-md-3"><label class="form-label">Năm học</label><input class="form-control" list="academic-years" name="academic" value="<?= ui_h($selectedAcademic) ?>" placeholder="Ví dụ: 2026-27" required></div>
            <div class="col-md-3"><label class="form-label">Ngành</label><select class="form-select" name="branch" required><option value="">-- Chọn ngành --</option><?php foreach ($branches as $value => $label): ?><option value="<?= ui_h($value) ?>" <?= $selectedBranch === $value ? 'selected' : '' ?>><?= ui_h($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label">Học kỳ</label><select class="form-select" name="semester" required><option value="">-- Chọn --</option><?php for ($semester = 1; $semester <= 8; $semester++): ?><option value="<?= $semester ?>" <?= (int)$selectedSemester === $semester ? 'selected' : '' ?>>Học kỳ <?= $semester ?></option><?php endfor; ?></select></div>
            <div class="col-md-2"><label class="form-label">Lớp</label><input class="form-control" list="batch-options" name="batch" value="<?= ui_h($selectedBatch) ?>" placeholder="Ví dụ: A1" required></div>
            <div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search me-1"></i>Xem lịch</button></div>
        </form>
        <datalist id="academic-years"><?php foreach ($academicYears as $year): ?><option value="<?= ui_h($year) ?>"><?php endforeach; ?></datalist>
        <datalist id="batch-options"><?php foreach ($batches as $batch): ?><option value="<?= ui_h($batch) ?>"><?php endforeach; ?></datalist>

        <?php if ($showSchedule): ?>
            <h6 class="my-4 text-center text-primary">Thời khóa biểu: <?= ui_h($selectedAcademic) ?> · <?= ui_h(ui_branch_label($selectedBranch)) ?> · Lớp <?= ui_h($selectedBatch) ?> · Học kỳ <?= (int)$selectedSemester ?></h6>
            <?php if (!$entries): ?>
                <div class="alert alert-warning">Chưa có thời khóa biểu cho lựa chọn này. Bạn có thể tạo lịch mới bằng nút phía trên.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-bordered align-middle text-center"><thead><tr><th>Thứ</th><?php foreach ($usedSlots as $slot => $label): ?><th><?= ui_h($label) ?></th><?php endforeach; ?></tr></thead><tbody>
                    <?php foreach ($days as $day => $dayLabel): ?><tr><th><?= ui_h($dayLabel) ?></th><?php foreach ($usedSlots as $slot => $label): $entry = $grid[$day][$slot] ?? null; ?><td><?= $entry ? ui_h($entry['subject_name'] ?: 'Môn #' . $entry['subject_code']) : '<span class="text-muted">—</span>' ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        <?php endif; ?>
    </div></div></div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content">
    <form action="api_timetable.php?type=add" method="post" id="timetableCreateForm">
        <div class="modal-header"><h5 class="modal-title">Tạo thời khóa biểu</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
            <?= csrf_input() ?>
            <p class="text-muted">Chọn thông tin lớp trước. Sau đó thêm từng buổi học bằng <strong>ngày – tiết – môn học</strong>; danh sách môn sẽ tự lọc đúng ngành và học kỳ.</p>
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label">Năm học</label><input class="form-control" list="academic-years" name="academicyear" placeholder="Ví dụ: 2026-27" required></div>
                <div class="col-md-3"><label class="form-label">Ngành</label><select class="form-select" name="branch" id="createBranch" required><option value="">-- Chọn ngành --</option><?php foreach ($branches as $value => $label): ?><option value="<?= ui_h($value) ?>"><?= ui_h($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Học kỳ</label><select class="form-select" name="semester" id="createSemester" required><option value="">-- Chọn --</option><?php for ($semester = 1; $semester <= 8; $semester++): ?><option value="<?= $semester ?>">Học kỳ <?= $semester ?></option><?php endfor; ?></select></div>
                <div class="col-md-2"><label class="form-label">Lớp</label><input class="form-control" list="batch-options" name="batch" placeholder="Ví dụ: A1" required></div>
                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-primary w-100" id="addScheduleRow"><i class="fas fa-plus me-1"></i>Thêm buổi học</button></div>
            </div>
            <div class="alert alert-info py-2 mb-3">Mẹo: chỉ các môn thuộc ngành và học kỳ đã chọn mới xuất hiện, nên không phải tìm trong toàn bộ danh sách môn.</div>
            <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Ngày học</th><th>Tiết</th><th>Môn học</th><th></th></tr></thead><tbody id="scheduleRows"></tbody></table></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary">Lưu thời khóa biểu</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="api_timetable.php?type=delete" method="post">
        <div class="modal-header"><h5 class="modal-title">Xóa thời khóa biểu</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body"><?= csrf_input() ?><p class="text-danger">Thao tác này xóa toàn bộ các buổi học của lớp đã chọn.</p>
            <div class="mb-3"><label class="form-label">Năm học</label><input class="form-control" list="academic-years" name="academicyear" placeholder="Ví dụ: 2026-27" required></div>
            <div class="mb-3"><label class="form-label">Ngành</label><select class="form-select" name="branch" required><option value="">-- Chọn ngành --</option><?php foreach ($branches as $value => $label): ?><option value="<?= ui_h($value) ?>"><?= ui_h($label) ?></option><?php endforeach; ?></select></div>
            <div class="mb-3"><label class="form-label">Học kỳ</label><select class="form-select" name="semester" required><option value="">-- Chọn --</option><?php for ($semester = 1; $semester <= 8; $semester++): ?><option value="<?= $semester ?>">Học kỳ <?= $semester ?></option><?php endfor; ?></select></div>
            <div><label class="form-label">Lớp</label><input class="form-control" list="batch-options" name="batch" placeholder="Ví dụ: A1" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger">Xóa toàn bộ lịch</button></div>
    </form>
</div></div></div>

<script>
(function () {
    const subjects = <?= json_encode($subjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const days = <?= json_encode(ui_days(), JSON_UNESCAPED_UNICODE) ?>;
    const slots = <?= json_encode(ui_slot_labels(), JSON_UNESCAPED_UNICODE) ?>;
    const rows = document.getElementById('scheduleRows');
    const branch = document.getElementById('createBranch');
    const semester = document.getElementById('createSemester');
    const form = document.getElementById('timetableCreateForm');

    function esc(value) { const node = document.createElement('span'); node.textContent = value; return node.innerHTML; }
    function dayOptions() { return '<option value="">-- Chọn ngày --</option>' + Object.keys(days).map(key => '<option value="' + key + '">' + esc(days[key]) + '</option>').join(''); }
    function slotOptions() { return '<option value="">-- Chọn tiết --</option>' + Object.keys(slots).map(key => '<option value="' + key + '">' + esc(slots[key]) + '</option>').join(''); }
    function subjectOptions(selected) {
        const valid = subjects.filter(item => item.branch === branch.value && Number(item.semester) === Number(semester.value));
        if (!branch.value || !semester.value) return '<option value="">Chọn ngành và học kỳ trước</option>';
        let html = '<option value="">-- Chọn môn học --</option>';
        valid.forEach(item => { html += '<option value="' + item.subject_code + '"' + (Number(selected) === Number(item.subject_code) ? ' selected' : '') + '>' + esc('[' + item.subject_code + '] ' + item.name) + '</option>'; });
        return html;
    }
    function refreshSubjects() {
        rows.querySelectorAll('.subject-choice').forEach(select => {
            const current = select.value;
            select.innerHTML = subjectOptions(current);
            select.disabled = !branch.value || !semester.value;
        });
    }
    function addRow() {
        const row = document.createElement('tr');
        row.innerHTML = '<td><select class="form-select" name="day[]" required>' + dayOptions() + '</select></td>'
            + '<td><select class="form-select" name="slot[]" required>' + slotOptions() + '</select></td>'
            + '<td><select class="form-select subject-choice" name="subject_code[]" required></select></td>'
            + '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Xóa buổi học"><i class="fas fa-trash"></i></button></td>';
        rows.appendChild(row);
        refreshSubjects();
    }
    document.getElementById('addScheduleRow').addEventListener('click', addRow);
    branch.addEventListener('change', refreshSubjects);
    semester.addEventListener('change', refreshSubjects);
    rows.addEventListener('click', function (event) { const button = event.target.closest('.remove-row'); if (button) button.closest('tr').remove(); });
    form.addEventListener('submit', function (event) { if (!rows.children.length) { event.preventDefault(); alert('Hãy thêm ít nhất một buổi học.'); } });
    addRow();
}());
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
