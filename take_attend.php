<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/attendance.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'TEACHER') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

$subjectCode = filter_var($_GET['subject_code'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot = filter_var($_GET['slot'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$semester = filter_var($_GET['semester'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$batch = trim((string)($_GET['batch'] ?? ''));
$day = trim((string)($_GET['day'] ?? ''));
$slotLabel = trim((string)($_GET['slotlabel'] ?? ''));
$academicYear = trim((string)($_GET['academic'] ?? ''));
if (!$subjectCode || !$slot || !$semester || $batch === '' || $day === '' || !preg_match('/^\d{4}-\d{2}$/', $academicYear)) {
    $_SESSION['msg'] = '<div class="alert alert-danger">Liên kết mở phiên điểm danh không hợp lệ.</div>';
    header('Location: take_attendance.php');
    exit();
}
if ($day !== date('l')) {
    http_response_code(403);
    exit('Chỉ có thể mở phiên điểm danh cho tiết học của hôm nay.');
}

$subject = db_row(
    'SELECT subject_code, name, branch FROM subjects WHERE subject_code = ? AND teacher_id = ? LIMIT 1',
    'ii',
    [(int)$subjectCode, (int)$_SESSION['teacher_id']]
);
if (!$subject) {
    http_response_code(403);
    exit('Bạn không có quyền mở phiên điểm danh cho môn học này.');
}
$timetable = db_row(
    'SELECT subject_code, slotlabel FROM timetable WHERE subject_code = ? AND day = ? AND slot = ? AND batch = ? AND semester = ? AND branch = ? AND academic_year = ? LIMIT 1',
    'isisiss',
    [(int)$subjectCode, $day, (int)$slot, $batch, (int)$semester, $subject['branch'], $academicYear]
);
if (!$timetable) {
    http_response_code(404);
    exit('Không tìm thấy tiết học hợp lệ trong thời khóa biểu.');
}
if (!attendance_slot_access_allowed((int)$slot, (string)$timetable['slotlabel'])) {
    http_response_code(403);
    exit('Chỉ có thể mở điểm danh trong đúng khung giờ của tiết học này.');
}

$attendanceSession = attendance_open_session(
    (int)$_SESSION['teacher_id'],
    (int)$subjectCode,
    (int)$slot,
    $batch,
    $subject['branch'],
    (int)$semester,
    (string)$timetable['slotlabel']
);
if (!$attendanceSession) {
    http_response_code(503);
    exit('Không thể mở phiên điểm danh. Hãy kiểm tra migration cơ sở dữ liệu.');
}

require __DIR__ . '/header.php';
$daysVI = ['Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'];
$qrRotateSeconds = max(30, min(180, (int)cfg('qr_rotate_seconds')));
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee"><li class="breadcrumb-item">Trang chủ</li><li class="breadcrumb-item">Điểm danh</li><li class="breadcrumb-item active">Mở phiên</li></ol></nav>
</div>

<main class="container-fluid pt-4 px-4">
    <section class="bg-light rounded h-100 p-4">
        <h1 class="h5 mb-3">Điểm danh: <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="alert alert-info">QR được thay mới mỗi <?= $qrRotateSeconds ?> giây. Sau khi quét thành công, sinh viên có tối đa 2 phút để hoàn thành xác minh khuôn mặt.</div>

        <div class="attendance-session-grid">
            <div class="attendance-summary table-responsive">
                <table class="table table-bordered align-middle"><tbody>
                    <tr><th>Môn học</th><td><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th>Lớp</th><td><?= htmlspecialchars($batch, ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th>Học kỳ</th><td><?= (int)$semester ?></td></tr>
                    <tr><th>Ngày</th><td><?= date('d/m/Y') ?></td></tr>
                    <tr><th>Thứ</th><td><?= htmlspecialchars($daysVI[date('l')] ?? date('l'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th>Tiết</th><td><?= htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th>Thời gian còn lại</th><td id="sessionExpiry">--</td></tr>
                </tbody></table>
            </div>
            <div class="attendance-qr-panel text-center">
                <div id="qrStatus" class="mb-2">Đang tạo QR an toàn…</div>
                <div id="attendanceQr" class="attendance-qr border rounded d-inline-flex justify-content-center align-items-center" style="width:300px; height:300px; background:#fff;"></div>
            </div>
        </div>

        <h2 class="h6 text-success mt-4">Nhật ký điểm danh thời gian thực</h2>
        <ul id="notifications" class="list-unstyled mb-0"></ul>
    </section>
</main>

<script src="js/vendor/qrcode.min.js"></script>
<script>
(function () {
    const sessionId = <?= json_encode((int)$attendanceSession['id']) ?>;
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const rotateSeconds = <?= json_encode($qrRotateSeconds) ?>;
    const qrContainer = document.getElementById('attendanceQr');
    const qrStatus = document.getElementById('qrStatus');
    const expiry = document.getElementById('sessionExpiry');
    let qrInstance = null;
    let remaining = 0;

    function updateCountdown() {
        expiry.textContent = remaining > 0 ? remaining + ' giây' : 'Đang làm mới';
        if (remaining > 0) remaining--;
    }
    async function refreshQr() {
        try {
            const body = new URLSearchParams({session_id: sessionId, csrf_token: csrfToken});
            const response = await fetch('api_qr_token.php', {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body});
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'Không thể tạo QR');

            if (!qrInstance) {
                qrContainer.innerHTML = '';
                qrInstance = new QRCode(qrContainer, {
                    text: data.token,
                    width: 280,
                    height: 280,
                    correctLevel: QRCode.CorrectLevel.M
                });
            } else {
                qrInstance.makeCode(data.token);
            }

            qrStatus.textContent = 'QR hợp lệ trong ' + data.expires_in + ' giây';
            remaining = data.expires_in;
        } catch (error) {
            qrStatus.textContent = error.message || 'Không thể làm mới QR.';
        }
    }
    refreshQr();
    setInterval(refreshQr, rotateSeconds * 1000);
    setInterval(updateCountdown, 1000);

    function fetchNotifications() {
        const body = new URLSearchParams({
            csrf_token: csrfToken,
            subject_code: <?= json_encode((int)$subjectCode) ?>,
            branch: <?= json_encode($subject['branch']) ?>,
            batch: <?= json_encode($batch) ?>,
            semester: <?= json_encode((int)$semester) ?>,
            slot: <?= json_encode((int)$slot) ?>
        });
        fetch('api_noti.php', {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'}, body})
            .then(function (response) { return response.ok ? response.json() : []; })
            .then(function (data) {
                if (!Array.isArray(data)) return;
                const list = $('#notifications').empty();
                data.forEach(function (item) {
                    $('<li>', {class: 'notification border rounded p-2 mb-1 text-success'}).text(item.time + ': ' + item.name).prependTo(list);
                });
            });
    }
    fetchNotifications();
    setInterval(fetchNotifications, 3000);
})();
</script>
<?php require __DIR__ . '/footer.php'; ?>
