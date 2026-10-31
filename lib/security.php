<?php
/**
 * Security primitives shared by the application.  Keep this file dependency-free
 * because it is loaded before any HTML is emitted.
 */

if (defined('QRCAS_SECURITY_LOADED')) {
    return;
}
define('QRCAS_SECURITY_LOADED', true);

function security_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function client_ip(): string
{
    // REMOTE_ADDR is supplied by the web server. Do not trust client-controlled
    // X-Forwarded-For headers unless a trusted reverse proxy is configured.
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/**
 * Phân tích chuỗi CIDR (phân tách bởi dấu phẩy hoặc dòng mới),
 * loại bỏ khoảng trắng và dòng trống. Trả về mảng các CIDR hợp lệ dạng "ip/prefix".
 *
 * @param string $raw
 * @return array
 */
function cidr_list_parse(string $raw): array
{
    $tokens = preg_split('/[\r\n,]+/', $raw);
    if (!is_array($tokens)) {
        return [];
    }

    $valid = [];
    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }

        $parts = explode('/', $token);
        if (count($parts) !== 2) {
            continue;
        }

        $ipStr = trim($parts[0]);
        $prefixStr = trim($parts[1]);

        if (!ctype_digit($prefixStr)) {
            continue;
        }

        $prefix = (int)$prefixStr;
        if (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($prefix >= 0 && $prefix <= 32) {
                $valid[] = $ipStr . '/' . $prefix;
            }
        } elseif (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($prefix >= 0 && $prefix <= 128) {
                $valid[] = $ipStr . '/' . $prefix;
            }
        }
    }
    return $valid;
}

/**
 * Kiểm tra xem địa chỉ IP có nằm trong ít nhất một dải CIDR trong chuỗi $cidrs hay không.
 * Hỗ trợ IPv4, IPv6 và IPv4-mapped IPv6 (::ffff:a.b.c.d).
 * IP hoặc CIDR sai định dạng thì coi là không khớp (trả về false).
 *
 * @param string $ip
 * @param string $cidrs
 * @return bool
 */
function ip_in_cidrs(string $ip, string $cidrs): bool
{
    $ip = trim($ip);
    if ($ip === '') {
        return false;
    }

    $ipBinary = @inet_pton($ip);
    if ($ipBinary === false) {
        return false;
    }

    $parsedCidrs = cidr_list_parse($cidrs);
    if (empty($parsedCidrs)) {
        return false;
    }

    $isIpv4Mapped = (strlen($ipBinary) === 16 && substr($ipBinary, 0, 12) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff");
    $ipv4BinaryFromMapped = $isIpv4Mapped ? substr($ipBinary, 12, 4) : null;

    foreach ($parsedCidrs as $cidr) {
        [$cidrIp, $prefixStr] = explode('/', $cidr, 2);
        $prefix = (int)$prefixStr;
        $cidrBinary = @inet_pton($cidrIp);
        if ($cidrBinary === false) {
            continue;
        }

        // Trường hợp CIDR là IPv4 (4 bytes)
        if (strlen($cidrBinary) === 4) {
            $candidateIp = null;
            if (strlen($ipBinary) === 4) {
                $candidateIp = $ipBinary;
            } elseif ($ipv4BinaryFromMapped !== null) {
                $candidateIp = $ipv4BinaryFromMapped;
            }

            if ($candidateIp !== null && cidr_match_binary($candidateIp, $cidrBinary, $prefix, 32)) {
                return true;
            }
        }
        // Trường hợp CIDR là IPv6 (16 bytes)
        elseif (strlen($cidrBinary) === 16) {
            $candidateIp = null;
            if (strlen($ipBinary) === 16) {
                $candidateIp = $ipBinary;
            } elseif (strlen($ipBinary) === 4) {
                if (substr($cidrBinary, 0, 12) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" && $prefix >= 96) {
                    $candidateIp = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . $ipBinary;
                }
            }

            if ($candidateIp !== null && cidr_match_binary($candidateIp, $cidrBinary, $prefix, 128)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * So sánh bit giữa hai chuỗi nhị phân IP theo prefix.
 */
function cidr_match_binary(string $ipBin, string $cidrBin, int $prefix, int $maxBits): bool
{
    if ($prefix < 0 || $prefix > $maxBits) {
        return false;
    }
    if ($prefix === 0) {
        return true;
    }

    $wholeBytes = intdiv($prefix, 8);
    $remBits = $prefix % 8;

    if ($wholeBytes > 0) {
        if (substr($ipBin, 0, $wholeBytes) !== substr($cidrBin, 0, $wholeBytes)) {
            return false;
        }
    }

    if ($remBits > 0) {
        $mask = (0xFF << (8 - $remBits)) & 0xFF;
        $ipByte = ord($ipBin[$wholeBytes]);
        $cidrByte = ord($cidrBin[$wholeBytes]);
        if (($ipByte & $mask) !== ($cidrByte & $mask)) {
            return false;
        }
    }

    return true;
}

function csrf_token(): string
{
    security_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_is_valid(?string $token): bool
{
    security_start_session();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}

function password_is_hashed(string $value): bool
{
    $info = password_get_info($value);
    return !empty($info['algo']);
}

/** Allows a one-time transparent migration from the legacy plaintext database. */
function verify_password_compat(string $password, string $stored): bool
{
    if (password_is_hashed($stored)) {
        return password_verify($password, $stored);
    }
    return hash_equals($stored, $password);
}

function set_logged_in_session(array $account, string $role): void
{
    security_start_session();
    session_regenerate_id(true);
    unset($_SESSION['enrollment_no'], $_SESSION['teacher_id']);
    $_SESSION['username'] = $account['name'] ?? $account['username'] ?? '';
    $_SESSION['usertype'] = $role;
    $_SESSION['logged'] = true;

    if ($role === 'STUDENT') {
        $_SESSION['enrollment_no'] = (int)$account['enrollment_no'];
    } elseif ($role === 'TEACHER') {
        $_SESSION['teacher_id'] = (int)$account['id'];
    }
}
