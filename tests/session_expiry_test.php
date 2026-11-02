<?php
// tests/session_expiry_test.php
require_once __DIR__ . '/_db_bootstrap.php';

// Check if attendance_test_mode is already in app_config
$oldRow = db_row("SELECT v FROM app_config WHERE k = 'attendance_test_mode'");
$hadTestModeConfig = ($oldRow !== null);
$oldTestModeValue = $hadTestModeConfig ? (string)$oldRow['v'] : null;

// Enable test mode BEFORE first cfg() call
cfg_set('attendance_test_mode', '1');

$testTeacherId = 990001;
$testSubjectCode = 990001;
$testSlot = 1;
$testBatch = 'TEST_BATCH';
$testBranch = 'TEST_BRANCH';
$testSemester = 1;
$sessionId = null;

try {
    $session = attendance_open_session($testTeacherId, $testSubjectCode, $testSlot, $testBatch, $testBranch, $testSemester);
    if (!$session || empty($session['id'])) {
        throw new RuntimeException("Failed to open attendance session");
    }
    $sessionId = (int)$session['id'];

    $token = attendance_new_qr_token($sessionId);
    if (!$token) {
        throw new RuntimeException("Failed to generate QR token");
    }

    $minutes = max(1, min(120, (int)cfg('checkin_minutes')));
    $expectedSessionSeconds = $minutes * 60;

    $seconds = max(30, min(180, (int)cfg('qr_rotate_seconds')));
    $expectedTokenSeconds = $seconds;

    $sessionDiff = (int)db_val('SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) FROM attendance_sessions WHERE id = ?', 'i', [$sessionId]);
    $tokenDiff = (int)db_val('SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) FROM attendance_qr_tokens WHERE attendance_session_id = ? ORDER BY id DESC LIMIT 1', 'i', [$sessionId]);

    if (abs($sessionDiff - $expectedSessionSeconds) > 5) {
        throw new RuntimeException("Session expiry diff {$sessionDiff}s exceeds tolerance from expected {$expectedSessionSeconds}s");
    }

    if (abs($tokenDiff - $expectedTokenSeconds) > 5) {
        throw new RuntimeException("Token expiry diff {$tokenDiff}s exceeds tolerance from expected {$expectedTokenSeconds}s");
    }

    echo "[PASS] session_expiry_test: session expiry diff {$sessionDiff}s (expected {$expectedSessionSeconds}s), token expiry diff {$tokenDiff}s (expected {$expectedTokenSeconds}s)\n";
} finally {
    if ($sessionId !== null) {
        db_exec('DELETE FROM attendance_sessions WHERE id = ?', 'i', [$sessionId]);
    }
    if ($hadTestModeConfig) {
        db_exec("UPDATE app_config SET v = ? WHERE k = 'attendance_test_mode'", 's', [$oldTestModeValue]);
    } else {
        db_exec("DELETE FROM app_config WHERE k = 'attendance_test_mode'");
    }
}
