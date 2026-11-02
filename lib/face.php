<?php
/**
 * Các hàm xử lý mẫu khuôn mặt. Ảnh webcam chỉ được dùng trong trình duyệt;
 * máy chủ chỉ nhận vector 128 chiều và kết quả thử thách người thật.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

if (defined('QRCAS_FACE_LOADED')) {
    return;
}
define('QRCAS_FACE_LOADED', true);

function face_mode(): string
{
    $mode = (string)cfg('face_mode');
    return in_array($mode, ['off', 'flag', 'always'], true) ? $mode : 'always';
}

function face_parse_descriptor($payload): ?array
{
    if (is_string($payload)) {
        $payload = json_decode($payload, true);
    }
    if (!is_array($payload) || count($payload) !== 128) {
        return null;
    }

    $descriptor = [];
    foreach ($payload as $value) {
        if (!is_numeric($value)) {
            return null;
        }
        $number = (float)$value;
        if (!is_finite($number) || $number < -2.0 || $number > 2.0) {
            return null;
        }
        $descriptor[] = $number;
    }
    return $descriptor;
}

function face_parse_descriptor_set($payload, int $minSamples = 1, int $maxSamples = 8): ?array
{
    if (is_string($payload)) {
        $payload = json_decode($payload, true);
    }
    if (!is_array($payload) || count($payload) < $minSamples || count($payload) > $maxSamples) {
        return null;
    }

    $descriptors = [];
    foreach ($payload as $value) {
        $descriptor = face_parse_descriptor($value);
        if ($descriptor === null) {
            return null;
        }
        $descriptors[] = $descriptor;
    }
    return $descriptors;
}

function face_average_descriptor(array $descriptors): array
{
    $average = array_fill(0, 128, 0.0);
    foreach ($descriptors as $descriptor) {
        for ($i = 0; $i < 128; $i++) {
            $average[$i] += $descriptor[$i];
        }
    }
    $count = count($descriptors);
    for ($i = 0; $i < 128; $i++) {
        $average[$i] /= $count;
    }
    return $average;
}

function face_distance(array $first, array $second): float
{
    $sum = 0.0;
    for ($i = 0; $i < 128; $i++) {
        $difference = $first[$i] - $second[$i];
        $sum += $difference * $difference;
    }
    return sqrt($sum);
}

function face_profile(int $enrollmentNo): ?array
{
    return db_row(
        'SELECT enrollment_no, descriptor, status, consented_at, updated_at FROM student_face_profiles WHERE enrollment_no = ? LIMIT 1',
        'i',
        [$enrollmentNo]
    );
}

function face_store_profile(int $enrollmentNo, array $descriptors): array
{
    // Nếu student_face_profiles đã có hàng cho enrollment_no này thì TỪ CHỐI ghi đè
    if (face_profile($enrollmentNo)) {
        return ['ok' => false, 'message' => 'Hồ sơ khuôn mặt đã tồn tại. Liên hệ quản trị viên để đặt lại.'];
    }

    $average = face_average_descriptor($descriptors);
    $consistencyLimit = max(0.30, min(0.80, (float)cfg('face_threshold')));
    foreach ($descriptors as $descriptor) {
        if (face_distance($average, $descriptor) > $consistencyLimit) {
            return ['ok' => false, 'message' => 'Các mẫu khuôn mặt không nhất quán. Hãy đăng ký lại ở nơi đủ sáng.'];
        }
    }

    // Không cho cùng một khuôn mặt được đăng ký cho tài khoản khác.
    // Ngưỡng chống trùng mặt giữa các tài khoản dùng max(face_dup_threshold, face_threshold)
    $duplicateLimit = max((float)cfg('face_dup_threshold'), (float)cfg('face_threshold'));
    foreach (db_all('SELECT enrollment_no, descriptor FROM student_face_profiles WHERE enrollment_no <> ?', 'i', [$enrollmentNo]) as $profile) {
        $other = face_parse_descriptor($profile['descriptor']);
        if ($other !== null && face_distance($average, $other) < $duplicateLimit) {
            return ['ok' => false, 'message' => 'Khuôn mặt này đã được liên kết với một tài khoản khác. Hãy liên hệ quản trị viên.'];
        }
    }

    $status = (string)cfg('require_device_approval') === '1' ? 'pending' : 'approved';
    $encoded = json_encode($average, JSON_PRESERVE_ZERO_FRACTION);
    if ($encoded === false || db_exec(
        'INSERT INTO student_face_profiles (enrollment_no, descriptor, status, consented_at, created_at, updated_at)
         VALUES (?, ?, ?, NOW(), NOW(), NOW())',
        'iss',
        [$enrollmentNo, $encoded, $status]
    ) === false) {
        return ['ok' => false, 'message' => 'Không thể lưu mẫu khuôn mặt. Hãy kiểm tra migration cơ sở dữ liệu.'];
    }
    $message = $status === 'pending'
        ? 'Đã đăng ký mẫu khuôn mặt. Hồ sơ đang chờ quản trị viên duyệt trước khi có thể điểm danh.'
        : 'Đã đăng ký mẫu khuôn mặt.';
    return ['ok' => true, 'message' => $message, 'status' => $status];
}

function face_match_profile(int $enrollmentNo, $candidate): array
{
    $profile = face_profile($enrollmentNo);
    if (!$profile) {
        return ['ok' => false, 'message' => 'Bạn chưa đăng ký khuôn mặt. Vào Hồ sơ cá nhân để đăng ký trước khi điểm danh.'];
    }
    if (($profile['status'] ?? 'approved') === 'pending') {
        return ['ok' => false, 'message' => 'Hồ sơ khuôn mặt của bạn đang chờ quản trị viên duyệt.'];
    }
    $stored = face_parse_descriptor($profile['descriptor']);
    $submitted = face_parse_descriptor($candidate);
    if ($stored === null || $submitted === null) {
        return ['ok' => false, 'message' => 'Dữ liệu xác minh khuôn mặt không hợp lệ.'];
    }

    $distance = face_distance($stored, $submitted);
    $threshold = max(0.30, min(0.80, (float)cfg('face_threshold')));
    if ($distance > $threshold) {
        return ['ok' => false, 'message' => 'Khuôn mặt không khớp với hồ sơ đã đăng ký.', 'distance' => $distance];
    }
    return ['ok' => true, 'distance' => $distance];
}

function face_issue_challenge(int $attendanceSessionId, int $enrollmentNo): array
{
    security_start_session();
    $choices = ['blink', 'left', 'right'];
    $id = bin2hex(random_bytes(16));
    $_SESSION['face_challenges'] = [
        $id => [
            'attendance_session_id' => $attendanceSessionId,
            'enrollment_no' => $enrollmentNo,
            'challenge' => $choices[random_int(0, count($choices) - 1)],
            // QR đã được kiểm tra trước khi tạo thử thách; cho thêm thời gian
            // mở camera, tải mô hình và hoàn thành bước xác minh người thật.
            'expires_at' => time() + 120,
        ],
    ];
    $challenge = $_SESSION['face_challenges'][$id];
    return ['id' => $id, 'challenge' => $challenge['challenge'], 'expires_in' => 120];
}

function face_consume_challenge(string $id, int $enrollmentNo): ?array
{
    security_start_session();
    $challenge = $_SESSION['face_challenges'][$id] ?? null;
    unset($_SESSION['face_challenges'][$id]);
    if (!is_array($challenge)
        || !isset($challenge['attendance_session_id'], $challenge['enrollment_no'], $challenge['challenge'], $challenge['expires_at'])
        || !hash_equals((string)$challenge['enrollment_no'], (string)$enrollmentNo)
        || (int)$challenge['expires_at'] < time()) {
        return null;
    }
    if (!in_array($challenge['challenge'], ['blink', 'left', 'right'], true)) {
        return null;
    }
    return [
        'attendance_session_id' => (int)$challenge['attendance_session_id'],
        'challenge' => (string)$challenge['challenge'],
    ];
}
