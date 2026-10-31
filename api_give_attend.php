<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/attendance.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/face.php';
require_once __DIR__ . '/lib/passkey.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function attendance_response(int $status, bool $ok, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

// 1. Đăng nhập
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    attendance_response(405, false, 'Chỉ chấp nhận yêu cầu POST.');
}
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    attendance_response(401, false, 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
}

// 2. CSRF
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    attendance_response(403, false, 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và quét lại mã.');
}

// 3. IP trường (Hạng mục 2)
$campusCidrs = trim((string)cfg('campus_cidrs'));
if ($campusCidrs !== '') {
    $ip = client_ip();
    if (!ip_in_cidrs($ip, $campusCidrs)) {
        attendance_response(403, false, 'Hãy kết nối Wi-Fi của trường để điểm danh.');
    }
}

// 4. GPS / accuracy
$token = trim((string)($_POST['token'] ?? ''));
$lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lon = filter_var($_POST['lon'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = filter_var($_POST['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lon === false || $accuracy === false || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180 || $accuracy < 0) {
    attendance_response(422, false, 'Không nhận được vị trí hợp lệ. Hãy bật GPS và thử lại.');
}

$maxAccuracy = max(10, (float)cfg('geo_max_accuracy_m'));
if ($accuracy > $maxAccuracy) {
    attendance_response(422, false, 'Độ chính xác GPS quá thấp. Hãy di chuyển đến nơi có tín hiệu tốt hơn và thử lại.');
}

// 5. Geo
$settings = db_row('SELECT lat, lon, covarage FROM settings WHERE id = 1 LIMIT 1');
if (!$settings || !is_numeric($settings['lat']) || !is_numeric($settings['lon']) || !is_numeric($settings['covarage'])) {
    attendance_response(503, false, 'Hệ thống chưa được cấu hình vị trí điểm danh.');
}
$distance = attendance_distance_km((float)$settings['lat'], (float)$settings['lon'], (float)$lat, (float)$lon);
if ($distance > (float)$settings['covarage']) {
    attendance_response(403, false, 'Bạn không ở trong khu vực được phép điểm danh.');
}

// 6. Challenge khuôn mặt / token
$enrollmentNo = (int)$_SESSION['enrollment_no'];
$faceVerification = null;
$challengeData = null;
if (face_mode() !== 'off') {
    // QR chỉ cần còn hạn tại thời điểm quét để tạo thử thách. Từ đó trở đi,
    // thử thách dùng một lần cho phép camera có đủ thời gian xác minh mà
    // không kéo dài tuổi thọ của mã QR.
    $challengeId = trim((string)($_POST['face_challenge_id'] ?? ''));
    $challengeData = $challengeId === '' ? null : face_consume_challenge($challengeId, $enrollmentNo);
    if ($challengeData === null) {
        attendance_response(422, false, 'Thử thách khuôn mặt đã hết hạn. Hãy quét QR lại để xác minh.');
    }
    $session = attendance_active_session((int)$challengeData['attendance_session_id']);
    if (!$session) {
        attendance_response(410, false, 'Phiên điểm danh đã đóng hoặc đã hết hạn.');
    }
} else {
    $session = attendance_session_from_token($token);
    if (!$session) {
        attendance_response(410, false, 'Mã QR đã hết hạn. Hãy quét mã hiện tại trên màn hình giảng viên.');
    }
}

// 7. Slot
if (!attendance_session_is_in_slot($session)) {
    attendance_response(403, false, 'Đã ngoài thời gian điểm danh của tiết học này.');
}

// 8. Thuộc lớp
$student = db_row(
    'SELECT enrollment_no FROM students WHERE enrollment_no = ? AND batch = ? AND semester = ? AND branch = ? LIMIT 1',
    'isis',
    [$enrollmentNo, $session['batch'], (int)$session['semester'], $session['branch']]
);
if (!$student) {
    attendance_response(403, false, 'Lớp học này không được phân công cho tài khoản của bạn.');
}

// 9. Passkey (Hạng mục 3)
if (passkey_mode() !== 'off') {
    $credIdB64 = trim((string)($_POST['passkey_credential_id'] ?? ''));
    $clientDataB64 = trim((string)($_POST['passkey_client_data_json'] ?? ''));
    $authDataB64 = trim((string)($_POST['passkey_authenticator_data'] ?? ''));
    $sigB64 = trim((string)($_POST['passkey_signature'] ?? ''));

    if ($credIdB64 === '' || $clientDataB64 === '' || $authDataB64 === '' || $sigB64 === '') {
        attendance_response(422, false, 'Thiếu dữ liệu xác thực passkey thiết bị.');
    }

    $rawCredId = passkey_base64url_decode($credIdB64);
    $clientDataJSON = passkey_base64url_decode($clientDataB64);
    $authenticatorData = passkey_base64url_decode($authDataB64);
    $signature = passkey_base64url_decode($sigB64);

    // Tiêu thụ challenge (dùng 1 lần, đúng enrollment_no, đúng attendance_session_id của $session, chưa hết hạn)
    $challenge = passkey_consume_auth_challenge($enrollmentNo, (int)$session['id']);
    if ($challenge === null) {
        attendance_response(422, false, 'Thử thách xác thực thiết bị đã hết hạn hoặc không hợp lệ. Vui lòng quét lại mã QR.');
    }

    // Tìm credential theo id và phải thuộc sinh viên này với status 'approved'
    $passkeyRow = passkey_find_credential($rawCredId);
    if (!$passkeyRow || (int)$passkeyRow['enrollment_no'] !== $enrollmentNo || $passkeyRow['status'] !== 'approved') {
        attendance_response(403, false, 'Thiết bị của bạn chưa được đăng ký hoặc chưa được quản trị viên duyệt.');
    }

    // Xác minh bằng thư viện WebAuthn (yêu cầu cờ user-verified)
    // Chống clone: nếu sign_count lưu > 0 thì sign_count mới phải lớn hơn; nếu cả hai bằng 0 thì chấp nhận
    try {
        $webauthn = passkey_webauthn();
        $prevSignCount = (int)$passkeyRow['sign_count'];
        $verified = $webauthn->processGet(
            $clientDataJSON,
            $authenticatorData,
            $signature,
            $passkeyRow['public_key'],
            $challenge,
            $prevSignCount,
            true, // requireUserVerification = true
            true  // requireUserPresent = true
        );
        if (!$verified) {
            attendance_response(403, false, 'Xác thực passkey thất bại.');
        }
        $newSignCount = $webauthn->getSignatureCounter() ?? $prevSignCount;
        passkey_update_usage((int)$passkeyRow['id'], $newSignCount);
    } catch (\Exception $e) {
        error_log('[give_attend_passkey] ' . $e->getMessage());
        attendance_response(403, false, 'Xác thực thiết bị không thành công.');
    }
}

// 10. Khuôn mặt
if (face_mode() !== 'off') {
    if (($_POST['liveness_passed'] ?? '') !== '1') {
        attendance_response(422, false, 'Chưa xác nhận được người thật. Hãy hoàn thành thử thách trên camera và quét lại QR.');
    }

    $match = face_match_profile($enrollmentNo, $_POST['face_descriptor'] ?? null);
    if (!$match['ok']) {
        attendance_response(403, false, $match['message']);
    }
    $faceVerification = ['distance' => $match['distance'], 'challenge' => $challengeData['challenge']];
}

// 11. attendance_record()
$saved = attendance_record(
    (int)$session['id'],
    $enrollmentNo,
    $session,
    (float)$lat,
    (float)$lon,
    (float)$accuracy,
    client_ip(),
    (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
    $faceVerification
);
if (!$saved) {
    attendance_response(409, false, 'Bạn đã điểm danh trong phiên này hoặc yêu cầu bị trùng.');
}

attendance_response(201, true, 'Điểm danh thành công.');
