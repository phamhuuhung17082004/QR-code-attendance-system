<?php
// tests/duplicate_attendance_test.php
require_once __DIR__ . '/_db_bootstrap.php';

// Check if attendance_test_mode is already in app_config
$oldRow = db_row("SELECT v FROM app_config WHERE k = 'attendance_test_mode'");
$hadTestModeConfig = ($oldRow !== null);
$oldTestModeValue = $hadTestModeConfig ? (string)$oldRow['v'] : null;

// Enable test mode BEFORE first cfg() call
cfg_set('attendance_test_mode', '1');

$testEnrollment1 = 990001;
$testEnrollment2 = 990002;
$testSubjectCode = 990001;
$testSlot = 1;
$testBatch = 'TEST_BATCH';
$testBranch = 'TEST_BRANCH';
$testSemester = 1;
$testTeacherId = 990001;

$createdSessionIds = [];

try {
    // Kịch bản chính:
    // (a) Mở phiên 1, ghi nhận OK cho sinh viên 990001
    $session1 = attendance_open_session($testTeacherId, $testSubjectCode, $testSlot, $testBatch, $testBranch, $testSemester);
    if (!$session1 || empty($session1['id'])) {
        throw new RuntimeException("Failed to open session 1");
    }
    $session1Id = (int)$session1['id'];
    $createdSessionIds[] = $session1Id;

    $rec1 = attendance_record($session1Id, $testEnrollment1, $session1, 0.0, 0.0, 10.0, '127.0.0.1', 'TestAgent');
    if (!$rec1) {
        throw new RuntimeException("Scenario (a) failed: attendance_record in session 1 returned false");
    }

    // (b) Cùng phiên lần 2 bị từ chối
    $rec2 = attendance_record($session1Id, $testEnrollment1, $session1, 0.0, 0.0, 10.0, '127.0.0.1', 'TestAgent');
    if ($rec2) {
        throw new RuntimeException("Scenario (b) failed: attendance_record duplicate in session 1 should be rejected");
    }

    // (c) Đóng phiên 1, mở phiên 2 cùng tiết, ghi nhận bị từ chối
    db_exec('UPDATE attendance_sessions SET closed_at = NOW() WHERE id = ?', 'i', [$session1Id]);

    $session2 = attendance_open_session($testTeacherId, $testSubjectCode, $testSlot, $testBatch, $testBranch, $testSemester);
    if (!$session2 || empty($session2['id'])) {
        throw new RuntimeException("Failed to open session 2");
    }
    $session2Id = (int)$session2['id'];
    $createdSessionIds[] = $session2Id;

    $rec3 = attendance_record($session2Id, $testEnrollment1, $session2, 0.0, 0.0, 10.0, '127.0.0.1', 'TestAgent');
    if ($rec3) {
        throw new RuntimeException("Scenario (c) failed: attendance_record in session 2 should be rejected");
    }

    // (d) Còn đúng 1 dòng attendance cho testEnrollment1
    $count1 = (int)db_val('SELECT COUNT(*) FROM attendance WHERE enrollment_no = ? AND subject_code = ? AND slot = ? AND batch = ?', 'siis', [$testEnrollment1, $testSubjectCode, $testSlot, $testBatch]);
    if ($count1 !== 1) {
        throw new RuntimeException("Scenario (d) failed: expected 1 attendance record, found {$count1}");
    }

    // Kịch bản phụ: chèn một dòng attendance giả lập "điểm danh thủ công" trước, rồi attendance_record() bị từ chối
    $today = date('Y-m-d');
    $dayName = date('l');
    $nowTime = date('H:i:s');
    $manualInsert = db_exec(
        'INSERT INTO attendance (enrollment_no, date, day, subject_code, slot, batch, branch, semester, time, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'issiississ',
        [$testEnrollment2, $today, $dayName, $testSubjectCode, $testSlot, $testBatch, $testBranch, $testSemester, $nowTime, '127.0.0.1']
    );
    if ($manualInsert === false) {
        throw new RuntimeException("Failed to insert manual attendance for student 2");
    }

    $recManual = attendance_record($session2Id, $testEnrollment2, $session2, 0.0, 0.0, 10.0, '127.0.0.1', 'TestAgent');
    if ($recManual) {
        throw new RuntimeException("Sub-scenario failed: attendance_record after manual attendance should be rejected");
    }

    $count2 = (int)db_val('SELECT COUNT(*) FROM attendance WHERE enrollment_no = ? AND subject_code = ? AND slot = ? AND batch = ?', 'siis', [$testEnrollment2, $testSubjectCode, $testSlot, $testBatch]);
    if ($count2 !== 1) {
        throw new RuntimeException("Sub-scenario failed: expected 1 attendance record for student 2, found {$count2}");
    }

    echo "[PASS] duplicate_attendance_test: all duplicate scenarios properly rejected, exactly 1 attendance record remained\n";
} finally {
    // Cleanup attendance and attendance_noti
    db_exec('DELETE FROM attendance WHERE enrollment_no IN (?, ?)', 'ii', [$testEnrollment1, $testEnrollment2]);
    db_exec('DELETE FROM attendance_noti WHERE enrollment_no IN (?, ?)', 'ii', [$testEnrollment1, $testEnrollment2]);

    // Cleanup sessions (cascades to tokens, claims, face verifications)
    foreach ($createdSessionIds as $sid) {
        db_exec('DELETE FROM attendance_sessions WHERE id = ?', 'i', [$sid]);
    }

    // Restore app_config
    if ($hadTestModeConfig) {
        db_exec("UPDATE app_config SET v = ? WHERE k = 'attendance_test_mode'", 's', [$oldTestModeValue]);
    } else {
        db_exec("DELETE FROM app_config WHERE k = 'attendance_test_mode'");
    }
}
