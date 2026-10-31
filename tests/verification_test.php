<?php
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/passkey.php';

session_save_path(sys_get_temp_dir());
security_start_session();

echo "=== Verifying Security Logic ===\n";

// Test 1: Replay challenge defense
passkey_save_auth_challenge(12345, 999, 'test_challenge_bytes');
$c1 = passkey_consume_auth_challenge(12345, 999);
if ($c1 !== 'test_challenge_bytes') {
    echo "[FAIL] Challenge 1st consume mismatch\n";
    exit(1);
}
$c2 = passkey_consume_auth_challenge(12345, 999);
if ($c2 !== null) {
    echo "[FAIL] Replay protection failed: second consume must return null\n";
    exit(1);
}
echo "[PASS] Replay protection: challenge cannot be consumed twice\n";

// Test 2: Different student defense
passkey_save_auth_challenge(12345, 999, 'test_challenge_student');
$cWrongStudent = passkey_consume_auth_challenge(99999, 999);
if ($cWrongStudent !== null) {
    echo "[FAIL] Challenge consumed with wrong student ID\n";
    exit(1);
}
echo "[PASS] Different student defense: challenge rejected for other students\n";

// Test 3: Different session defense
passkey_save_auth_challenge(12345, 999, 'test_challenge_session');
$cWrongSession = passkey_consume_auth_challenge(12345, 888);
if ($cWrongSession !== null) {
    echo "[FAIL] Challenge consumed with wrong attendance session ID\n";
    exit(1);
}
echo "[PASS] Different session defense: challenge rejected for other attendance sessions\n";

// Test 4: Base64url encode/decode
$raw = "binary_bytes_\x00\xFF\xFE\x01\x80\x7F";
$enc = passkey_base64url_encode($raw);
$dec = passkey_base64url_decode($enc);
if ($dec !== $raw) {
    echo "[FAIL] Base64url roundtrip mismatch\n";
    exit(1);
}
echo "[PASS] Base64URL encoding/decoding roundtrip\n";

// Test 5: Check uploads/.htaccess
$htaccess = file_get_contents(__DIR__ . '/../uploads/.htaccess');
if (strpos($htaccess, 'Require all denied') === false || strpos($htaccess, 'Deny from all') === false) {
    echo "[FAIL] uploads/.htaccess missing access restrictions\n";
    exit(1);
}
echo "[PASS] uploads/.htaccess access controls verified\n";

// Test 6: Verify no external qrserver.com call
$takeAttend = file_get_contents(__DIR__ . '/../take_attend.php');
if (strpos($takeAttend, 'qrserver.com') !== false) {
    echo "[FAIL] take_attend.php still references qrserver.com\n";
    exit(1);
}
if (strpos($takeAttend, 'qrcode.min.js') === false) {
    echo "[FAIL] take_attend.php does not load qrcode.min.js\n";
    exit(1);
}
echo "[PASS] take_attend.php verified (uses client-side qrcode.min.js, no qrserver.com)\n";

echo "=== All Security Verification Checks PASSED ===\n";
exit(0);

