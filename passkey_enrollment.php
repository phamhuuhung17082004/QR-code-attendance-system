<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/passkey.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

$enrollmentNo = (int)$_SESSION['enrollment_no'];
$isHttps = passkey_is_https_or_localhost();
$passkeys = db_all(
    'SELECT id, label, status, created_at, approved_at, last_used_at FROM student_passkeys WHERE enrollment_no = ? ORDER BY id DESC',
    'i',
    [$enrollmentNo]
);
$activeCount = passkey_count_active_credentials($enrollmentNo);
$maxDevices = max(1, (int)cfg('max_devices'));

require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item"><a href="profile_stud.php">Hồ sơ cá nhân</a></li>
            <li class="breadcrumb-item active" aria-current="page">Đăng ký thiết bị (Passkey)</li>
        </ol>
    </nav>
</div>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-2">Đăng ký thiết bị điểm danh (WebAuthn Passkey)</h6>
                <p class="text-muted mb-3">
                    Đăng ký khóa bảo mật gắn với phần cứng thiết bị (vân tay, FaceID hoặc mã PIN thiết bị).
                    Khi điểm danh, hệ thống sẽ yêu cầu xác thực trực tiếp trên thiết bị này để chống điểm danh hộ.
                </p>

                <?php if (!$isHttps): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Passkey cần HTTPS:</strong> Trình duyệt chỉ cho phép sử dụng WebAuthn qua kết nối HTTPS an toàn (hoặc localhost).
                    </div>
                <?php endif; ?>

                <div id="passkeyAlert" class="alert alert-info" role="alert">
                    Chọn tên gợi nhớ cho thiết bị rồi bấm nút đăng ký bên dưới.
                </div>

                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card p-3 mb-3 border">
                            <h6 class="card-title mb-3">Thêm thiết bị mới (Hiện có: <?= $activeCount ?>/<?= $maxDevices ?>)</h6>
                            <?php if ($activeCount >= $maxDevices): ?>
                                <div class="alert alert-warning mb-0">
                                    Bạn đã đạt số lượng thiết bị tối đa cho phép (<?= $maxDevices ?> thiết bị).
                                    Nếu bạn đổi thiết bị mới, vui lòng liên hệ quản trị viên để thu hồi thiết bị cũ.
                                </div>
                            <?php else: ?>
                                <form id="passkeyForm" onsubmit="return false;">
                                    <div class="mb-3">
                                        <label for="deviceLabel" class="form-label">Tên thiết bị gợi nhớ</label>
                                        <input type="text" class="form-control" id="deviceLabel" placeholder="Ví dụ: iPhone của tôi, Laptop cá nhân" required maxlength="100">
                                        <div class="form-text">Đặt tên giúp bạn và quản trị viên dễ phân biệt khi quản lý thiết bị.</div>
                                    </div>
                                    <button type="button" id="btnRegister" class="btn btn-primary" <?= !$isHttps ? 'disabled' : '' ?>>
                                        <i class="fas fa-fingerprint me-2"></i>Đăng ký thiết bị này
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100 bg-white">
                            <h6 class="mb-2"><i class="fas fa-info-circle text-primary me-2"></i>Lưu ý quan trọng</h6>
                            <ul class="mb-0 small text-muted ps-3">
                                <li class="mb-1">Hệ thống yêu cầu xác minh sinh trắc học (vân tay, FaceID, Windows Hello) tích hợp sẵn trên thiết bị.</li>
                                <li class="mb-1">Sau khi đăng ký, thiết bị có thể cần quản trị viên phê duyệt trước khi bạn có thể dùng để điểm danh.</li>
                                <li class="mb-1">Không chia sẻ tài khoản hoặc mật khẩu với người khác.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3">Danh sách thiết bị đã đăng ký</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên thiết bị</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                                <th>Ngày duyệt</th>
                                <th>Lần dùng cuối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($passkeys)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Chưa có thiết bị nào được đăng ký.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($passkeys as $index => $pk): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($pk['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td>
                                            <?php if ($pk['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Đã duyệt (Sẵn sàng)</span>
                                            <?php elseif ($pk['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Chờ quản trị viên duyệt</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Đã thu hồi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($pk['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($pk['approved_at']) ? htmlspecialchars($pk['approved_at'], ENT_QUOTES, 'UTF-8') : '<em class="text-muted">Chưa duyệt</em>' ?></td>
                                        <td><?= !empty($pk['last_used_at']) ? htmlspecialchars($pk['last_used_at'], ENT_QUOTES, 'UTF-8') : '<em class="text-muted">Chưa dùng</em>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const alertBox = document.getElementById('passkeyAlert');
    const btnRegister = document.getElementById('btnRegister');
    const deviceLabel = document.getElementById('deviceLabel');

    function notify(text, type) {
        alertBox.className = 'alert alert-' + type;
        alertBox.textContent = text;
    }

    function base64urlToBuffer(base64url) {
        let str = base64url.replace(/-/g, '+').replace(/_/g, '/');
        while (str.length % 4) {
            str += '=';
        }
        const binary = atob(str);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    if (btnRegister) {
        btnRegister.addEventListener('click', async function () {
            if (!window.PublicKeyCredential) {
                notify('Trình duyệt của bạn không hỗ trợ WebAuthn / Passkey. Vui lòng nâng cấp trình duyệt hoặc dùng thiết bị khác.', 'danger');
                return;
            }

            const labelValue = (deviceLabel ? deviceLabel.value.trim() : '') || 'Thiết bị của tôi';
            btnRegister.disabled = true;
            notify('Đang kết nối với hệ thống để chuẩn bị đăng ký passkey…', 'info');

            try {
                // Bước 1: Gọi begin
                const beginBody = new URLSearchParams({
                    action: 'begin',
                    csrf_token: csrfToken
                });
                const beginRes = await fetch('api_passkey_register.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
                    body: beginBody
                });
                const beginData = await beginRes.json();
                if (!beginData.ok) {
                    throw new Error(beginData.message || 'Không thể khởi tạo đăng ký.');
                }

                const createArgs = beginData.create_args;
                const pk = createArgs.publicKey;

                // Chuẩn bị options cho navigator.credentials.create
                pk.challenge = base64urlToBuffer(pk.challenge);
                pk.user.id = typeof pk.user.id === 'string' ? base64urlToBuffer(pk.user.id) : new Uint8Array(pk.user.id).buffer;

                if (Array.isArray(pk.excludeCredentials)) {
                    pk.excludeCredentials = pk.excludeCredentials.map(function (item) {
                        return {
                            type: item.type,
                            id: typeof item.id === 'string' ? base64urlToBuffer(item.id) : new Uint8Array(item.id).buffer,
                            transports: item.transports || []
                        };
                    });
                }

                notify('Vui lòng làm theo hướng dẫn trên thiết bị để xác thực sinh trắc học (vân tay, FaceID, mã PIN)…', 'warning');

                // Bước 2: Tạo credential qua WebAuthn API
                const credential = await navigator.credentials.create({ publicKey: pk });
                if (!credential) {
                    throw new Error('Không nhận được thông tin xác thực từ thiết bị.');
                }

                notify('Đang gửi dữ liệu xác thực lên máy chủ để hoàn tất…', 'info');

                // Bước 3: Gửi finish
                const finishBody = new URLSearchParams({
                    action: 'finish',
                    csrf_token: csrfToken,
                    label: labelValue,
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    attestationObject: bufferToBase64url(credential.response.attestationObject)
                });

                const finishRes = await fetch('api_passkey_register.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
                    body: finishBody
                });
                const finishData = await finishRes.json();
                if (!finishData.ok) {
                    throw new Error(finishData.message || 'Không thể hoàn tất đăng ký passkey.');
                }

                notify(finishData.message, 'success');
                setTimeout(function () {
                    window.location.reload();
                }, 1800);
            } catch (err) {
                notify(err.message || 'Đã xảy ra lỗi trong quá trình đăng ký passkey.', 'danger');
                btnRegister.disabled = false;
            }
        });
    }
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>

