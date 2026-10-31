<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['type']) && $_GET['type'] === 'update') {
    require_csrf();

    $location = trim((string)($_POST['location'] ?? ''));
    $lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
    $lon = filter_var($_POST['lon'] ?? null, FILTER_VALIDATE_FLOAT);
    $covarage = filter_var($_POST['covarage'] ?? null, FILTER_VALIDATE_FLOAT);

    // Validate campus_cidrs
    $rawCidrs = trim((string)($_POST['campus_cidrs'] ?? ''));
    if (strlen($rawCidrs) > 255) {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dải IP Wi-Fi vượt quá giới hạn 255 ký tự.</div>';
        header('Location: api_settings_update.php');
        exit();
    }

    if ($rawCidrs !== '') {
        $tokens = preg_split('/[\r\n,]+/', $rawCidrs);
        $invalidCidrs = [];
        $validCidrs = [];
        foreach ($tokens as $token) {
            $t = trim($token);
            if ($t === '') continue;
            $parts = explode('/', $t);
            $isValid = false;
            if (count($parts) === 2 && ctype_digit(trim($parts[1]))) {
                $ipStr = trim($parts[0]);
                $prefix = (int)$parts[1];
                if (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $prefix >= 0 && $prefix <= 32) {
                    $isValid = true;
                } elseif (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $prefix >= 0 && $prefix <= 128) {
                    $isValid = true;
                }
            }
            if ($isValid) {
                $validCidrs[] = trim($parts[0]) . '/' . (int)$parts[1];
            } else {
                $invalidCidrs[] = $t;
            }
        }

        if (!empty($invalidCidrs)) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dải CIDR không hợp lệ: ' . htmlspecialchars(implode(', ', $invalidCidrs), ENT_QUOTES, 'UTF-8') . '. Vui lòng kiểm tra lại.</div>';
            header('Location: api_settings_update.php');
            exit();
        }
        $normalizedCidrs = implode(',', $validCidrs);
        if (strlen($normalizedCidrs) > 255) {
            $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Dải IP Wi-Fi chuẩn hóa vượt quá giới hạn 255 ký tự.</div>';
            header('Location: api_settings_update.php');
            exit();
        }
        cfg_set('campus_cidrs', $normalizedCidrs);
    } else {
        cfg_set('campus_cidrs', '');
    }

    if ($lat === false || $lon === false || $covarage === false) {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Tọa độ hoặc bán kính không hợp lệ!</div>';
        header('Location: api_settings_update.php');
        exit();
    }

    $result = db_exec(
        'UPDATE `settings` SET `location` = ?, `lat` = ?, `lon` = ?, `covarage` = ? WHERE `id` = 1',
        'sddd',
        [$location, (float)$lat, (float)$lon, (float)$covarage]
    );

    if ($result !== false) {
        $_SESSION['msg'] = '<div class="alert alert-success mb-2" role="alert">Cập nhật cài đặt thành công.</div>';
        header('Location: settings.php');
        exit();
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Đã xảy ra lỗi, vui lòng thử lại!</div>';
        header('Location: settings.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
