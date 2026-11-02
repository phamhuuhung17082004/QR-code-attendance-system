<?php
/**
 * lib/db.php
 * Trợ giúp truy vấn an toàn (prepared statement) + bảng cấu hình app_config.
 * Nạp tự động từ conn.php nên dùng được ở mọi trang.
 */

if (defined('QRCAS_DB_LOADED')) {
    return;
}
define('QRCAS_DB_LOADED', true);

/**
 * Chạy câu lệnh có tham số. $types theo bind_param ('i','s','d'...).
 * Trả về mysqli_stmt đã execute hoặc false nếu lỗi.
 */
function db_stmt(string $sql, string $types = '', array $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('[QRCAS] prepare failed: ' . $conn->error . ' | ' . $sql);
        return false;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        // 1062 = trùng khóa duy nhất: để nơi gọi tự xử lý qua $conn->errno
        if ($stmt->errno !== 1062) {
            error_log('[QRCAS] execute failed: ' . $stmt->error . ' | ' . $sql);
        }
        $GLOBALS['qrcas_last_errno'] = $stmt->errno;
        $stmt->close();
        return false;
    }
    $GLOBALS['qrcas_last_errno'] = 0;
    return $stmt;
}

function db_last_errno(): int
{
    return (int)($GLOBALS['qrcas_last_errno'] ?? 0);
}

/** Trả về mảng các dòng. */
function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_stmt($sql, $types, $params);
    if (!$stmt) {
        return [];
    }
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** Trả về 1 dòng hoặc null. */
function db_row(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

/** Trả về giá trị ô đầu tiên hoặc null. */
function db_val(string $sql, string $types = '', array $params = [])
{
    $row = db_row($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** INSERT/UPDATE/DELETE. Trả về số dòng ảnh hưởng, hoặc false nếu lỗi. */
function db_exec(string $sql, string $types = '', array $params = [])
{
    global $conn;
    $stmt = db_stmt($sql, $types, $params);
    if (!$stmt) {
        return false;
    }
    $n = $stmt->affected_rows;
    $stmt->close();
    return $n;
}

function db_insert_id(): int
{
    global $conn;
    return (int)$conn->insert_id;
}

/* ------------------------------------------------------------------ */
/* Cấu hình hệ thống (bảng app_config, có giá trị mặc định trong code) */
/* ------------------------------------------------------------------ */

function cfg_defaults(): array
{
    return [
        // QR động
        'qr_rotate_seconds'       => '45',   // đổi mã mỗi N giây
        'qr_grace_seconds'        => '10',   // dung sai mạng
        'checkin_minutes'         => '20',   // phiên điểm danh mở trong N phút
        'enforce_slot_time'       => '0',    // 1 = chỉ mở phiên trong khung giờ của tiết học
        'attendance_test_mode'    => '0',    // 1 = chỉ dùng tạm khi kiểm thử; vẫn giữ QR/GPS/khuôn mặt
        // Thiết bị (passkey)
        'require_device_approval' => '1',    // thiết bị/khuôn mặt mới phải được quản trị viên duyệt
        'max_devices'             => '2',    // số thiết bị tối đa mỗi sinh viên
        'passkey_mode'            => 'always', // off | always
        // Khuôn mặt
        // Mặc định bắt buộc: QR chỉ được ghi nhận sau khi khuôn mặt đã khớp
        // và người dùng hoàn thành thử thách liveness.
        'face_mode'               => 'always', // off | flag | always
        'face_threshold'          => '0.50', // khoảng cách Euclid tối đa để coi là cùng người
        'face_dup_threshold'      => '0.40', // hai tài khoản khác nhau mà mặt gần hơn mức này = nghi vấn
        // Chấm điểm rủi ro
        'risk_flag'               => '35',
        'risk_reject'             => '70',
        'campus_cidrs'            => '',     // dải IP Wi-Fi của trường, cách nhau bằng dấu phẩy
        'geo_max_accuracy_m'      => '100',
        'ip_lookup'               => '1',    // 1 = tra cứu VPN/proxy/quốc gia qua ip-api.com
        'retention_days'          => '30',   // xóa ảnh bằng chứng/vector mặt của lần thử sau N ngày
    ];
}

function cfg(string $key)
{
    static $cache = null;
    if ($cache === null) {
        $cache = cfg_defaults();
        global $conn;
        $res = @$conn->query('SELECT k, v FROM app_config');
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $cache[$r['k']] = $r['v'];
            }
        }
    }
    return $cache[$key] ?? null;
}

function cfg_set(string $key, string $value): bool
{
    if (!array_key_exists($key, cfg_defaults())) {
        return false;
    }
    return db_exec(
        'INSERT INTO app_config (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)',
        'ss',
        [$key, $value]
    ) !== false;
}

/* ------------------------------------------------------------------ */
/* Chống dò mật khẩu                                                   */
/* ------------------------------------------------------------------ */

const LOGIN_LOCKOUT_PAIR_LIMIT = 8;
const LOGIN_LOCKOUT_IDENTITY_LIMIT = 30;
const LOGIN_LOCKOUT_IP_LIMIT = 100;

function login_is_locked(string $identity, string $ip): bool
{
    $pairLimit = LOGIN_LOCKOUT_PAIR_LIMIT;
    $identityLimit = LOGIN_LOCKOUT_IDENTITY_LIMIT;
    $ipLimit = LOGIN_LOCKOUT_IP_LIMIT;

    // 1. Khoá theo cặp (identity, ip): >= 8 lần thất bại trong 10 phút
    $pairCount = (int)db_val(
        "SELECT COUNT(*) FROM login_attempts WHERE created_at > (NOW() - INTERVAL 10 MINUTE) AND identity = ? AND ip = ?",
        'ss',
        [$identity, $ip]
    );
    if ($pairCount >= $pairLimit) {
        return true;
    }

    // 2. Trần theo identity: >= 30 lần trong 10 phút (chống dò mật khẩu phân tán)
    $identityCount = (int)db_val(
        "SELECT COUNT(*) FROM login_attempts WHERE created_at > (NOW() - INTERVAL 10 MINUTE) AND identity = ?",
        's',
        [$identity]
    );
    if ($identityCount >= $identityLimit) {
        return true;
    }

    // 3. Trần theo IP: >= 100 lần trong 10 phút (chặn bão request)
    $ipCount = (int)db_val(
        "SELECT COUNT(*) FROM login_attempts WHERE created_at > (NOW() - INTERVAL 10 MINUTE) AND ip = ?",
        's',
        [$ip]
    );
    if ($ipCount >= $ipLimit) {
        return true;
    }

    return false;
}

function login_record_failure(string $identity, string $ip): void
{
    db_exec('INSERT INTO login_attempts (identity, ip, created_at) VALUES (?, ?, NOW())', 'ss', [$identity, $ip]);
    // Dọn dữ liệu cũ (1% số lần gọi)
    if (random_int(1, 100) === 1) {
        db_exec('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function login_clear_failures(string $identity, string $ip): void
{
    db_exec('DELETE FROM login_attempts WHERE identity = ? AND ip = ?', 'ss', [$identity, $ip]);
}
