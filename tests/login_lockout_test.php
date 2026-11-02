<?php
// tests/login_lockout_test.php
require_once __DIR__ . '/_db_bootstrap.php';

$identA = 'test:SV-A';
$identB = 'test:SV-B';
$ipX = '203.0.113.10';
$ipY = '203.0.113.20';

try {
    // 1. Initial state: neither locked
    if (login_is_locked($identA, $ipX)) {
        throw new RuntimeException("Initial state failed: A from X should not be locked");
    }
    if (login_is_locked($identB, $ipX)) {
        throw new RuntimeException("Initial state failed: B from X should not be locked");
    }
    if (login_is_locked($identA, $ipY)) {
        throw new RuntimeException("Initial state failed: A from Y should not be locked");
    }

    // 2. Record 8 failures for SV A from IP X
    for ($i = 0; $i < 8; $i++) {
        login_record_failure($identA, $ipX);
    }

    // SV A from IP X must be locked
    if (!login_is_locked($identA, $ipX)) {
        throw new RuntimeException("Step 2 failed: A from X should be locked after 8 failures");
    }

    // SV B from IP X must NOT be locked (8 errors from A do not block B from same IP)
    if (login_is_locked($identB, $ipX)) {
        throw new RuntimeException("Step 2 failed: B from X should NOT be locked");
    }

    // SV A from IP Y must NOT be locked (8 errors from A on IP X do not block A on IP Y)
    if (login_is_locked($identA, $ipY)) {
        throw new RuntimeException("Step 2 failed: A from Y should NOT be locked");
    }

    // 3. Record 1 failure for A from Y
    login_record_failure($identA, $ipY);

    // 4. Successful login for A from X -> clear failures for (A, X)
    login_clear_failures($identA, $ipX);

    // (A, X) must now be unlocked
    if (login_is_locked($identA, $ipX)) {
        throw new RuntimeException("Step 4 failed: A from X should be unlocked after login_clear_failures");
    }

    // (A, Y) failure attempt must NOT have been cleared
    $countAY = (int)db_val(
        "SELECT COUNT(*) FROM login_attempts WHERE identity = ? AND ip = ?",
        'ss',
        [$identA, $ipY]
    );
    if ($countAY !== 1) {
        throw new RuntimeException("Step 4 failed: A from Y should still have 1 failure record, found {$countAY}");
    }

    echo "[PASS] login_lockout_test: verified (identity, ip) pair lockout, cross-user isolation, and targeted clearing\n";
} finally {
    db_exec("DELETE FROM login_attempts WHERE identity LIKE 'test:%' OR ip LIKE '203.0.113.%'");
}
