<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/face.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

$profile = face_profile((int)$_SESSION['enrollment_no']);
$faceStatus = $profile['status'] ?? ($profile ? 'approved' : null);
$bannerClass = 'alert-warning';
$bannerText = 'Bạn chưa đăng ký khuôn mặt. Việc đăng ký là bắt buộc trước khi điểm danh.';
if ($profile) {
    if ($faceStatus === 'pending') {
        $bannerClass = 'alert-warning';
        $bannerText = 'Hồ sơ khuôn mặt của bạn đang chờ quản trị viên duyệt.';
    } else {
        $bannerClass = 'alert-success';
        $bannerText = 'Bạn đã đăng ký mẫu khuôn mặt. Bạn có thể đăng ký lại nếu diện mạo thay đổi đáng kể.';
    }
}

require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee"><li class="breadcrumb-item">Trang chủ</li><li class="breadcrumb-item active">Đăng ký khuôn mặt</li></ol></nav>
</div>

<div class="container-fluid pt-4 px-4"><div class="row bg-light rounded mx-0"><div class="col-12"><div class="bg-light rounded h-100 p-4">
    <h6>Đăng ký mẫu khuôn mặt</h6>
    <p class="mb-2">Mẫu này dùng để đối chiếu khi điểm danh. Hệ thống chỉ lưu vector đặc trưng 128 chiều, không lưu ảnh webcam gốc.</p>
    <div class="alert <?= $bannerClass ?>" id="enrollmentStatus">
        <?= htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="row"><div class="col-lg-6">
        <div id="faceMessage" class="alert alert-info">Đọc điều khoản và nhấn bắt đầu khi sẵn sàng.</div>
        <div class="camera-controls">
            <div>
                <label class="form-label" for="enrollmentCameraFacing">Camera đăng ký khuôn mặt</label>
                <select id="enrollmentCameraFacing" class="form-select">
                    <option value="user">Camera trước</option>
                    <option value="environment">Camera sau</option>
                </select>
            </div>
        </div>
        <div id="cameraPanel" hidden>
            <video id="enrollmentPreview" class="img-thumbnail camera-preview" playsinline muted></video>
        </div>
        <div class="form-check my-3">
            <input class="form-check-input" type="checkbox" id="faceConsent">
            <label class="form-check-label" for="faceConsent">Tôi đồng ý dùng mẫu khuôn mặt để xác minh điểm danh theo quy định của nhà trường.</label>
        </div>
        <button type="button" id="enrollButton" class="btn btn-primary"><i class="fas fa-camera me-2"></i><?= $profile ? 'Đăng ký lại khuôn mặt' : 'Bắt đầu đăng ký' ?></button>
        <button type="button" id="cancelButton" class="btn btn-outline-secondary ms-2" hidden>Hủy</button>
    </div><div class="col-lg-6 mt-3 mt-lg-0">
        <div class="border rounded p-3 h-100">
            <strong>Hướng dẫn</strong>
            <ol class="mb-0 mt-2"><li>Đảm bảo chỉ có một người trong khung hình và đủ ánh sáng.</li><li>Nhìn thẳng vào camera, không đeo khẩu trang hoặc kính râm.</li><li>Giữ yên trong vài giây để hệ thống lấy 5 mẫu.</li></ol>
        </div>
    </div></div>
</div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="js/qrcas-face.js"></script>
<script>
(function () {
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const button = document.getElementById('enrollButton');
    const cancel = document.getElementById('cancelButton');
    const consent = document.getElementById('faceConsent');
    const messageBox = document.getElementById('faceMessage');
    const panel = document.getElementById('cameraPanel');
    const video = document.getElementById('enrollmentPreview');
    let running = false;

    function message(text, level) {
        messageBox.className = 'alert alert-' + level;
        messageBox.textContent = text;
    }
    function stop() {
        QRCASFace.stopCamera();
        panel.hidden = true;
        cancel.hidden = true;
        button.disabled = false;
        running = false;
    }
    cancel.addEventListener('click', function () {
        stop();
        message('Đã hủy đăng ký khuôn mặt.', 'secondary');
    });
    button.addEventListener('click', async function () {
        if (running) return;
        if (!consent.checked) {
            message('Bạn cần xác nhận đồng ý trước khi đăng ký khuôn mặt.', 'warning');
            return;
        }
        running = true;
        button.disabled = true;
        cancel.hidden = false;
        panel.hidden = false;
        try {
            message('Đang nạp mô hình nhận diện…', 'info');
            await QRCASFace.loadModels();
            await QRCASFace.startCamera(video, document.getElementById('enrollmentCameraFacing').value);
            const samples = await QRCASFace.enroll(video, function (text) { message(text, 'info'); }, 5);
            message('Đang lưu mẫu khuôn mặt…', 'info');
            const body = new URLSearchParams({
                csrf_token: csrfToken,
                consent: '1',
                descriptors: JSON.stringify(samples.descriptors)
            });
            const response = await fetch('api_face_enroll.php', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
                body: body
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'Không thể lưu mẫu khuôn mặt.');
            document.getElementById('enrollmentStatus').className = 'alert alert-' + (result.status === 'pending' ? 'warning' : 'success');
            document.getElementById('enrollmentStatus').textContent = result.message;
            message(result.message, 'success');
        } catch (error) {
            message(error.message || 'Không thể đăng ký khuôn mặt. Hãy kiểm tra camera và thử lại.', 'danger');
        } finally {
            stop();
        }
    });
})();
</script>
<?php require __DIR__ . '/footer.php'; ?>
