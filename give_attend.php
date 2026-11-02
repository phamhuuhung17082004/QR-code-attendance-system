<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}
require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background:#eee">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item active" aria-current="page">Điểm danh</li>
        </ol>
    </nav>
</div>

<main class="container-fluid pt-4 px-4">
    <section class="bg-light rounded page-card">
        <h1 class="h5 mb-1">Điểm danh bằng QR, Passkey và khuôn mặt</h1>
        <p class="text-muted">Bật vị trí chính xác, quét QR do giảng viên hiển thị, xác thực trên thiết bị và làm theo hướng dẫn camera.</p>
        <div id="attendanceMessage" class="alert alert-info" role="alert">Đang yêu cầu quyền truy cập vị trí…</div>

        <section id="qrCameraPanel" class="text-center">
            <p class="mb-3"><strong>Bước 1:</strong> Quét mã QR đang hiển thị trên màn hình giảng viên.</p>
            <div class="camera-controls">
                <div>
                    <label class="form-label" for="qrCameraSelect">Camera quét QR</label>
                    <select id="qrCameraSelect" class="form-select" disabled>
                        <option>Đang tìm camera…</option>
                    </select>
                </div>
            </div>
            <video id="preview" class="img-thumbnail camera-preview" playsinline></video>
            <p class="small text-muted mt-2 mb-0">Có thể chọn camera trước hoặc sau. Camera sau thường quét QR rõ hơn.</p>
        </section>

        <section id="passkeyPanel" class="text-center" hidden>
            <p class="mb-3"><strong>Bước 2:</strong> Xác minh sinh trắc học trên thiết bị đã đăng ký.</p>
            <div id="passkeyPrompt" class="alert alert-info mb-3">Nhấn nút bên dưới để mở quét vân tay / FaceID trên thiết bị của bạn.</div>
            <button type="button" id="btnPasskeyVerify" class="btn btn-success btn-lg px-4 py-2">
                <i class="fas fa-fingerprint me-2"></i>Xác minh bằng vân tay/FaceID
            </button>
        </section>

        <section id="faceCameraPanel" class="text-center" hidden>
            <p class="mb-3"><strong>Bước 3:</strong> Chỉ để một khuôn mặt trong khung hình và thực hiện yêu cầu trên màn hình.</p>
            <div class="camera-controls">
                <div>
                    <label class="form-label" for="faceCameraFacing">Camera xác minh khuôn mặt</label>
                    <select id="faceCameraFacing" class="form-select">
                        <option value="user">Camera trước</option>
                        <option value="environment">Camera sau</option>
                    </select>
                </div>
            </div>
            <video id="facePreview" class="img-thumbnail camera-preview" playsinline muted></video>
        </section>
    </section>
</main>

<script src="js/vendor/instascan.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="js/qrcas-face.js"></script>
<script>
(function () {
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const alertBox = document.getElementById('attendanceMessage');
    const qrPanel = document.getElementById('qrCameraPanel');
    const passkeyPanel = document.getElementById('passkeyPanel');
    const btnPasskeyVerify = document.getElementById('btnPasskeyVerify');
    const facePanel = document.getElementById('faceCameraPanel');
    const faceVideo = document.getElementById('facePreview');
    const qrCameraSelect = document.getElementById('qrCameraSelect');
    const faceCameraFacing = document.getElementById('faceCameraFacing');
    let location = null;
    let scanner = null;
    let cameras = [];
    let cameraIndex = 0;
    let scannerRunning = false;
    let submitting = false;

    function message(text, level) {
        alertBox.className = 'alert alert-' + level;
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

    function formPost(url, values) {
        const body = new URLSearchParams(values);
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
            body: body
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                return {response: response, data: data};
            });
        });
    }

    function stopScanner() {
        if (!scanner || !scannerRunning) return Promise.resolve();
        return scanner.stop().catch(function () {}).then(function () {
            scannerRunning = false;
        });
    }

    function cameraName(item, index) {
        return item.name || ('Camera ' + (index + 1));
    }

    function populateCameraChoices() {
        qrCameraSelect.innerHTML = '';
        cameras.forEach(function (item, index) {
            const option = document.createElement('option');
            option.value = String(index);
            option.textContent = cameraName(item, index);
            qrCameraSelect.appendChild(option);
        });
        qrCameraSelect.disabled = cameras.length < 2;
    }

    function startSelectedCamera(index, announce) {
        if (!cameras.length || !scanner) return Promise.reject(new Error('Không tìm thấy camera để quét QR.'));
        cameraIndex = Math.max(0, Math.min(Number(index) || 0, cameras.length - 1));
        qrCameraSelect.value = String(cameraIndex);
        return stopScanner().then(function () {
            return scanner.start(cameras[cameraIndex]);
        }).then(function () {
            scannerRunning = true;
            if (announce) message('Đang dùng ' + cameraName(cameras[cameraIndex], cameraIndex) + '. Sẵn sàng quét QR.', 'success');
        });
    }

    function preferredCameraIndex() {
        const rear = cameras.findIndex(function (item) {
            return /back|rear|environment|sau/i.test(item.name || '');
        });
        return rear >= 0 ? rear : 0;
    }

    function resumeScanner() {
        qrPanel.hidden = false;
        facePanel.hidden = true;
        passkeyPanel.hidden = true;
        submitting = false;
        startSelectedCamera(cameraIndex, false).catch(function () {
            message('Không thể mở lại camera quét QR. Hãy tải lại trang.', 'danger');
        });
    }

    function startScanner() {
        scanner = new Instascan.Scanner({video: document.getElementById('preview'), scanPeriod: 5, mirror: false});
        scanner.addListener('scan', beginAttendance);
        Instascan.Camera.getCameras().then(function (found) {
            cameras = found || [];
            if (!cameras.length) throw new Error('Không tìm thấy camera để quét QR.');
            populateCameraChoices();
            return startSelectedCamera(preferredCameraIndex(), true);
        }).catch(function (error) {
            message(error.message || 'Không thể mở camera. Hãy cấp quyền camera và thử lại.', 'danger');
        });
    }

    qrCameraSelect.addEventListener('change', function () {
        startSelectedCamera(this.value, true).catch(function (error) {
            message(error.message || 'Không thể đổi camera.', 'danger');
        });
    });

    faceCameraFacing.addEventListener('change', function () {
        if (facePanel.hidden) return;
        message('Đang đổi camera xác minh khuôn mặt…', 'info');
        QRCASFace.startCamera(faceVideo, this.value).catch(function (error) {
            message(error.message || 'Không thể đổi camera xác minh.', 'danger');
        });
    });

    function updateLocation(position) {
        location = {lat: position.coords.latitude, lon: position.coords.longitude, accuracy: position.coords.accuracy};
        startScanner();
    }

    function locationError() {
        message('Cần cấp quyền vị trí chính xác để điểm danh. Hãy bật GPS, cho phép vị trí trong trình duyệt rồi tải lại trang.', 'danger');
    }

    function submitAttendance(token, faceData, passkeyData) {
        const values = {
            token: token,
            lat: location.lat,
            lon: location.lon,
            accuracy: location.accuracy,
            csrf_token: csrfToken
        };
        if (passkeyData) {
            values.passkey_credential_id = passkeyData.passkey_credential_id;
            values.passkey_client_data_json = passkeyData.passkey_client_data_json;
            values.passkey_authenticator_data = passkeyData.passkey_authenticator_data;
            values.passkey_signature = passkeyData.passkey_signature;
        }
        if (faceData) {
            values.face_challenge_id = faceData.challengeId;
            values.liveness_passed = faceData.passed ? '1' : '0';
            values.face_descriptor = JSON.stringify(faceData.descriptor || []);
        }
        return formPost('api_give_attend.php', values).then(function (result) {
            message(result.data.message || 'Không thể điểm danh.', result.data.ok ? 'success' : 'danger');
            return !!result.data.ok;
        });
    }

    async function verifyFace(token, challengeData, passkeyData) {
        qrPanel.hidden = true;
        passkeyPanel.hidden = true;
        facePanel.hidden = false;
        try {
            message('Đang mở camera và nạp mô hình nhận diện…', 'info');
            await QRCASFace.loadModels();
            await QRCASFace.startCamera(faceVideo, faceCameraFacing.value);
            const result = await QRCASFace.livenessAndCapture(faceVideo, challengeData.challenge, function (text) {
                message(text, 'info');
            });
            message('Đang đối chiếu khuôn mặt với hồ sơ…', 'info');
            return await submitAttendance(token, {
                challengeId: challengeData.challenge_id,
                passed: result.passed,
                descriptor: result.descriptor
            }, passkeyData);
        } finally {
            QRCASFace.stopCamera();
        }
    }

    function proceedToFace(token, faceData, passkeyData) {
        if (!faceData || !faceData.required) {
            return submitAttendance(token, null, passkeyData);
        }
        return verifyFace(token, faceData, passkeyData);
    }

    function waitForPasskeyGesture(token, challengeArgs, faceData) {
        qrPanel.hidden = true;
        facePanel.hidden = true;
        passkeyPanel.hidden = false;
        message('Đã quét QR. Vui lòng nhấn nút bên dưới để xác minh vân tay/FaceID trên thiết bị.', 'info');

        return new Promise(function (resolve, reject) {
            function onVerifyClick() {
                btnPasskeyVerify.removeEventListener('click', onVerifyClick);
                btnPasskeyVerify.disabled = true;
                message('Đang kích hoạt xác minh trên thiết bị…', 'info');

                try {
                    const pk = challengeArgs.publicKey;
                    pk.challenge = base64urlToBuffer(pk.challenge);
                    if (Array.isArray(pk.allowCredentials)) {
                        pk.allowCredentials = pk.allowCredentials.map(function (item) {
                            return {
                                type: item.type,
                                id: typeof item.id === 'string' ? base64urlToBuffer(item.id) : new Uint8Array(item.id).buffer,
                                transports: item.transports || []
                            };
                        });
                    }

                    navigator.credentials.get({ publicKey: pk }).then(function (assertion) {
                        if (!assertion) throw new Error('Không nhận được phản hồi xác thực từ thiết bị.');
                        const passkeyData = {
                            passkey_credential_id: bufferToBase64url(assertion.rawId),
                            passkey_client_data_json: bufferToBase64url(assertion.response.clientDataJSON),
                            passkey_authenticator_data: bufferToBase64url(assertion.response.authenticatorData),
                            passkey_signature: bufferToBase64url(assertion.response.signature)
                        };
                        passkeyPanel.hidden = true;
                        btnPasskeyVerify.disabled = false;
                        message('Xác thực thiết bị thành công! Tiếp tục kiểm tra khuôn mặt…', 'success');
                        return proceedToFace(token, faceData, passkeyData);
                    }).then(resolve).catch(function (err) {
                        passkeyPanel.hidden = true;
                        btnPasskeyVerify.disabled = false;
                        reject(err);
                    });
                } catch (e) {
                    passkeyPanel.hidden = true;
                    btnPasskeyVerify.disabled = false;
                    reject(e);
                }
            }
            btnPasskeyVerify.addEventListener('click', onVerifyClick);
        });
    }

    function beginAttendance(token) {
        if (submitting || !location) return;
        submitting = true;
        message('Đã quét QR. Đang chuẩn bị thử thách xác thực…', 'info');

        let passkeyInfo = null;

        stopScanner().then(function () {
            // Bước 1: Gọi api_passkey_challenge ngay lúc quét (khi token còn tươi)
            return formPost('api_passkey_challenge.php', {token: token, csrf_token: csrfToken});
        }).then(function (result) {
            if (!result.data.ok) throw new Error(result.data.message || 'Không thể xác thực QR.');
            passkeyInfo = result.data;
            // Bước 2: Gọi NGAY api_face_challenge bằng cùng qr_token (trước khi bấm passkey)
            return formPost('api_face_challenge.php', {token: token, csrf_token: csrfToken});
        }).then(function (faceResult) {
            if (!faceResult.data.ok) throw new Error(faceResult.data.message || 'Không thể xác minh QR.');
            const faceData = faceResult.data;

            // Nếu không yêu cầu passkey (cfg passkey_mode = off)
            if (!passkeyInfo.required) {
                return proceedToFace(token, faceData, null);
            }

            // Nếu yêu cầu passkey, kiểm tra xem trình duyệt có hỗ trợ PublicKeyCredential
            if (!window.PublicKeyCredential) {
                throw new Error('Trình duyệt của bạn không hỗ trợ WebAuthn / Passkey.');
            }

            // Hiện nút bấm xác minh vân tay / FaceID (yêu cầu user gesture)
            return waitForPasskeyGesture(token, passkeyInfo.challenge, faceData);
        }).then(function (saved) {
            if (!saved) resumeScanner();
        }).catch(function (error) {
            QRCASFace.stopCamera();
            message(error.message || 'Không thể hoàn tất điểm danh. Hãy quét lại QR.', 'danger');
            resumeScanner();
        });
    }

    if (!navigator.geolocation) {
        locationError();
        return;
    }
    navigator.geolocation.getCurrentPosition(updateLocation, locationError, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
    });
})();
</script>
<?php require __DIR__ . '/footer.php'; ?>
