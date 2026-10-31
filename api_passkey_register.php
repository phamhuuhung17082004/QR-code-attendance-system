<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/passkey.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function passkey_reg_response(int $status, bool $ok, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    passkey_reg_response(405, false, 'Chỉ chấp nhận yêu cầu POST.');
}

if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    passkey_reg_response(401, false, 'Phiên đăng nhập đã hết hạn.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    passkey_reg_response(403, false, 'Yêu cầu không hợp lệ hoặc đã hết hạn.');
}

if (!passkey_is_https_or_localhost()) {
    passkey_reg_response(400, false, 'Passkey cần HTTPS.');
}

$enrollmentNo = (int)$_SESSION['enrollment_no'];
$action = trim((string)($_POST['action'] ?? ''));

if ($action === 'begin') {
    $activeCount = passkey_count_active_credentials($enrollmentNo);
    $maxDevices = max(1, (int)cfg('max_devices'));
    if ($activeCount >= $maxDevices) {
        passkey_reg_response(422, false, "Bạn đã đạt giới hạn tối đa thiết bị ({$maxDevices}). Vui lòng liên hệ quản trị viên để thu hồi thiết bị cũ.");
    }

    $existingCreds = passkey_get_all_credentials_for_student($enrollmentNo);
    $excludeIds = array_column($existingCreds, 'credential_id');

    $student = db_row('SELECT enrollment_no, name FROM students WHERE enrollment_no = ? LIMIT 1', 'i', [$enrollmentNo]);
    $displayName = !empty($student['name']) ? (string)$student['name'] : ('SV ' . $enrollmentNo);

    try {
        $webauthn = passkey_webauthn();
        $createArgs = $webauthn->getCreateArgs(
            (string)$enrollmentNo,
            (string)$enrollmentNo,
            $displayName,
            60,
            'discouraged',
            'required',
            false,
            $excludeIds
        );
        passkey_save_registration_challenge($enrollmentNo, $webauthn->getChallenge());
        passkey_reg_response(200, true, 'Sẵn sàng tạo passkey.', ['create_args' => $createArgs]);
    } catch (\Exception $e) {
        error_log('[passkey_register_begin] ' . $e->getMessage());
        passkey_reg_response(500, false, 'Không thể khởi tạo phiên đăng ký passkey. Vui lòng thử lại.');
    }
} elseif ($action === 'finish') {
    $clientDataB64 = trim((string)($_POST['clientDataJSON'] ?? ''));
    $attestationB64 = trim((string)($_POST['attestationObject'] ?? ''));
    $label = trim((string)($_POST['label'] ?? ''));
    if ($label === '') {
        $label = 'Thiết bị ' . date('d/m/Y H:i');
    }
    if (mb_strlen($label) > 100) {
        $label = mb_substr($label, 0, 100);
    }

    if ($clientDataB64 === '' || $attestationB64 === '') {
        passkey_reg_response(422, false, 'Dữ liệu đăng ký passkey không đầy đủ.');
    }

    $clientDataJSON = passkey_base64url_decode($clientDataB64);
    $attestationObject = passkey_base64url_decode($attestationB64);

    $activeCount = passkey_count_active_credentials($enrollmentNo);
    $maxDevices = max(1, (int)cfg('max_devices'));
    if ($activeCount >= $maxDevices) {
        passkey_reg_response(422, false, "Bạn đã đạt giới hạn tối đa thiết bị ({$maxDevices}).");
    }

    $challenge = passkey_consume_registration_challenge($enrollmentNo);
    if (!$challenge) {
        passkey_reg_response(422, false, 'Phiên đăng ký passkey đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.');
    }

    try {
        $webauthn = passkey_webauthn();
        $data = $webauthn->processCreate($clientDataJSON, $attestationObject, $challenge, true, true, false);
    } catch (\Exception $e) {
        error_log('[passkey_register_finish] ' . $e->getMessage());
        passkey_reg_response(422, false, 'Xác minh passkey thất bại. Vui lòng thử lại.');
    }

    if (empty($data->userVerified)) {
        passkey_reg_response(422, false, 'Thiết bị không xác thực sinh trắc học hoặc mã PIN. Vui lòng thử lại.');
    }

    $rawCredId = $data->credentialId instanceof \lbuchs\WebAuthn\Binary\ByteBuffer
        ? $data->credentialId->getBinaryString()
        : (string)$data->credentialId;

    if (passkey_find_credential($rawCredId)) {
        passkey_reg_response(422, false, 'Thiết bị này đã được đăng ký trên hệ thống.');
    }

    $requireApproval = (string)cfg('require_device_approval') === '1';
    $status = $requireApproval ? 'pending' : 'approved';
    $publicKeyPem = (string)$data->credentialPublicKey;
    $signCount = (int)($data->signatureCounter ?? 0);

    $saved = passkey_store_credential($enrollmentNo, $rawCredId, $publicKeyPem, $signCount, $label, $status);
    if (!$saved) {
        passkey_reg_response(500, false, 'Không thể lưu passkey vào cơ sở dữ liệu. Vui lòng thử lại.');
    }

    $message = $status === 'pending'
        ? 'Đăng ký thiết bị thành công! Thiết bị đang chờ quản trị viên phê duyệt trước khi có thể điểm danh.'
        : 'Đăng ký thiết bị thành công! Thiết bị đã được kích hoạt và sẵn sàng điểm danh.';

    passkey_reg_response(200, true, $message, ['status' => $status]);
} else {
    passkey_reg_response(400, false, 'Hành động không hợp lệ.');
}

