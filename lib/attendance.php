<?php
require_once __DIR__ . '/db.php';

if (defined('QRCAS_ATTENDANCE_LOADED')) {
    return;
}
define('QRCAS_ATTENDANCE_LOADED', true);

function attendance_distance_km(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo): float
{
    $earthRadius = 6371.0;
    $latDelta = deg2rad($latitudeTo - $latitudeFrom);
    $lonDelta = deg2rad($longitudeTo - $longitudeFrom);
    $a = sin($latDelta / 2) ** 2
        + cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * sin($lonDelta / 2) ** 2;
    return 2 * $earthRadius * asin(min(1, sqrt($a)));
}

/** Chuyển một mốc giờ như "07:50" hoặc "11:00 AM" thành số giây trong ngày. */
function attendance_time_seconds(string $value): ?int
{
    if (!preg_match('/^\s*(\d{1,2}):(\d{2})\s*([AaPp])?\.?[Mm]?\.?\s*$/', $value, $parts)) {
        return null;
    }
    $hour = (int)$parts[1];
    $minute = (int)$parts[2];
    $meridiem = strtoupper($parts[3] ?? '');
    if ($minute > 59 || $hour > 23 || ($meridiem !== '' && ($hour < 1 || $hour > 12))) {
        return null;
    }
    if ($meridiem === 'A' && $hour === 12) {
        $hour = 0;
    } elseif ($meridiem === 'P' && $hour !== 12) {
        $hour += 12;
    }
    return ($hour * 3600) + ($minute * 60);
}

/** Lấy khung giờ ưu tiên từ slotlabel của thời khóa biểu, rồi mới dùng giờ chuẩn của từng tiết. */
function attendance_slot_window(int $slot, string $slotLabel = ''): ?array
{
    preg_match_all('/\b\d{1,2}:\d{2}\s*(?:[AaPp]\.?[Mm]\.?)?\b/', $slotLabel, $matches);
    if (count($matches[0]) >= 2) {
        $start = attendance_time_seconds($matches[0][0]);
        $end = attendance_time_seconds($matches[0][1]);
        if ($start !== null && $end !== null && $end > $start) {
            return ['start' => $start, 'end' => $end];
        }
    }

    $windows = [
        1 => ['07:00', '07:50'], 2 => ['07:50', '08:40'], 3 => ['08:50', '09:40'],
        4 => ['09:40', '10:30'], 5 => ['10:40', '11:30'], 6 => ['11:30', '12:20'],
        7 => ['12:30', '13:20'], 8 => ['13:20', '14:10'], 9 => ['14:20', '15:10'],
        10 => ['15:10', '16:00'], 11 => ['16:10', '17:00'], 12 => ['17:00', '17:50'],
    ];
    if (!isset($windows[$slot])) {
        return null;
    }
    return [
        'start' => attendance_time_seconds($windows[$slot][0]),
        'end' => attendance_time_seconds($windows[$slot][1]),
    ];
}

/** Chỉ cho điểm danh trong đúng giờ của tiết; không dùng giờ QR để thay thế giờ học. */
function attendance_slot_is_open(int $slot, string $slotLabel = '', ?DateTimeInterface $now = null): bool
{
    $window = attendance_slot_window($slot, $slotLabel);
    if ($window === null || $window['start'] === null || $window['end'] === null) {
        return false;
    }
    $now = $now ?? new DateTimeImmutable('now');
    $seconds = ((int)$now->format('H') * 3600) + ((int)$now->format('i') * 60) + (int)$now->format('s');
    return $seconds >= $window['start'] && $seconds <= $window['end'];
}

/** Chỉ quản trị viên được bật từ Cài đặt; không bỏ qua QR, GPS hay khuôn mặt. */
function attendance_test_mode_enabled(): bool
{
    return (string)cfg('attendance_test_mode') === '1';
}

function attendance_slot_access_allowed(int $slot, string $slotLabel = ''): bool
{
    return attendance_test_mode_enabled() || attendance_slot_is_open($slot, $slotLabel);
}

function attendance_slot_label_for_session(array $session): string
{
    $row = db_row(
        'SELECT slotlabel FROM timetable
         WHERE subject_code = ? AND day = ? AND slot = ? AND batch = ? AND branch = ? AND semester = ?
         ORDER BY academic_year DESC, id DESC LIMIT 1',
        'isissi',
        [
            (int)$session['subject_code'],
            date('l'),
            (int)$session['slot'],
            (string)$session['batch'],
            (string)$session['branch'],
            (int)$session['semester'],
        ]
    );
    return (string)($row['slotlabel'] ?? '');
}

function attendance_session_is_in_slot(array $session): bool
{
    return attendance_slot_access_allowed((int)$session['slot'], attendance_slot_label_for_session($session));
}

function attendance_open_session(int $teacherId, int $subjectCode, int $slot, string $batch, string $branch, int $semester, string $slotLabel = ''): ?array
{
    if (!attendance_slot_access_allowed($slot, $slotLabel)) {
        return null;
    }
    $existing = db_row(
        'SELECT * FROM attendance_sessions
         WHERE teacher_id = ? AND subject_code = ? AND slot = ? AND batch = ? AND branch = ? AND semester = ?
           AND expires_at > NOW() AND closed_at IS NULL
         ORDER BY id DESC LIMIT 1',
        'iiissi',
        [$teacherId, $subjectCode, $slot, $batch, $branch, $semester]
    );
    if ($existing) {
        return $existing;
    }

    $minutes = max(1, min(120, (int)cfg('checkin_minutes')));
    $expiresAt = date('Y-m-d H:i:s', time() + ($minutes * 60));
    if (db_exec(
        'INSERT INTO attendance_sessions (teacher_id, subject_code, slot, batch, branch, semester, starts_at, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)',
        'iiissis',
        [$teacherId, $subjectCode, $slot, $batch, $branch, $semester, $expiresAt]
    ) === false) {
        return null;
    }
    return db_row('SELECT * FROM attendance_sessions WHERE id = ?', 'i', [db_insert_id()]);
}

function attendance_new_qr_token(int $sessionId): ?string
{
    $seconds = max(30, min(180, (int)cfg('qr_rotate_seconds')));
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $hash = hash('sha256', $token, true);
    $expiresAt = date('Y-m-d H:i:s', time() + $seconds);

    $inserted = db_exec(
        'INSERT INTO attendance_qr_tokens (attendance_session_id, token_hash, expires_at, created_at)
         VALUES (?, ?, ?, NOW())',
        'iss',
        [$sessionId, $hash, $expiresAt]
    );
    return $inserted === false ? null : $token;
}

function attendance_session_from_token(string $token): ?array
{
    if (!preg_match('/^[A-Za-z0-9_-]{40,60}$/', $token)) {
        return null;
    }
    $hash = hash('sha256', $token, true);
    return db_row(
        'SELECT s.* FROM attendance_qr_tokens t
         INNER JOIN attendance_sessions s ON s.id = t.attendance_session_id
         WHERE t.token_hash = ? AND t.expires_at >= NOW() AND s.expires_at >= NOW() AND s.closed_at IS NULL
         LIMIT 1',
        's',
        [$hash]
    );
}

function attendance_last_error(): string
{
    return (string)($GLOBALS['qrcas_attendance_last_error'] ?? '');
}

/** Trả về phiên điểm danh còn đang mở. */
function attendance_active_session(int $sessionId): ?array
{
    return db_row(
        'SELECT * FROM attendance_sessions WHERE id = ? AND expires_at >= NOW() AND closed_at IS NULL LIMIT 1',
        'i',
        [$sessionId]
    );
}

function attendance_record(
    int $sessionId,
    int $enrollmentNo,
    array $session,
    float $lat,
    float $lon,
    float $accuracy,
    string $ip,
    string $userAgent,
    ?array $faceVerification = null
): bool
{
    global $conn;
    $GLOBALS['qrcas_attendance_last_error'] = '';
    $now = new DateTimeImmutable('now');
    $date = $now->format('Y-m-d');
    $day = $now->format('l');
    $time = $now->format('H:i:s');

    $conn->begin_transaction();
    try {
        // Lock and re-check the session so an in-flight request cannot be
        // recorded after the teacher closes the session or it expires.
        $activeSession = db_row(
            'SELECT * FROM attendance_sessions WHERE id = ? AND expires_at >= NOW() AND closed_at IS NULL FOR UPDATE',
            'i',
            [$sessionId]
        );
        if (!$activeSession || !attendance_session_is_in_slot($activeSession)) {
            throw new RuntimeException('attendance session is no longer active');
        }

        $alreadyAttended = db_val(
            'SELECT 1 FROM attendance WHERE date = ? AND enrollment_no = ? AND subject_code = ? AND slot = ? AND batch = ? LIMIT 1',
            'siiis',
            [$date, $enrollmentNo, (int)$session['subject_code'], (int)$session['slot'], (string)$session['batch']]
        );
        if ($alreadyAttended) {
            $GLOBALS['qrcas_attendance_last_error'] = 'đã điểm danh trước đó';
            throw new RuntimeException('duplicate attendance for this slot');
        }

        // The unique key on this table makes duplicate requests harmless even when
        // two requests arrive at the same time.
        $claim = db_exec(
            'INSERT INTO attendance_claims (attendance_session_id, enrollment_no, latitude, longitude, accuracy_m, ip_address, user_agent, risk_score, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())',
            'iidddss',
            [$sessionId, $enrollmentNo, $lat, $lon, $accuracy, $ip, substr($userAgent, 0, 500)]
        );
        if ($claim === false) {
            throw new RuntimeException('duplicate claim');
        }

        if ($faceVerification !== null) {
            $faceSaved = db_exec(
                'INSERT INTO attendance_face_verifications (attendance_session_id, enrollment_no, face_distance, liveness_challenge, verified_at)
                 VALUES (?, ?, ?, ?, NOW())',
                'iids',
                [
                    $sessionId,
                    $enrollmentNo,
                    (float)$faceVerification['distance'],
                    (string)$faceVerification['challenge'],
                ]
            );
            if ($faceSaved === false) {
                throw new RuntimeException('face verification insert failed');
            }
        }

        $saved = db_exec(
            'INSERT INTO attendance (enrollment_no, date, day, subject_code, slot, batch, branch, semester, time, ip_address, attendance_session_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'issiississi',
            [
                $enrollmentNo,
                $date,
                $day,
                (int)$session['subject_code'],
                (int)$session['slot'],
                $session['batch'],
                $session['branch'],
                (int)$session['semester'],
                $time,
                $ip,
                $sessionId,
            ]
        );
        if ($saved === false) {
            if (db_last_errno() === 1062) {
                $GLOBALS['qrcas_attendance_last_error'] = 'đã điểm danh trước đó';
                throw new RuntimeException('duplicate attendance for this slot');
            }
            throw new RuntimeException('attendance insert failed');
        }

        $notified = db_exec(
            'INSERT INTO attendance_noti (enrollment_no, date, day, subject_code, slot, batch, branch, semester, time, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'issiississ',
            [
                $enrollmentNo,
                $date,
                $day,
                (int)$session['subject_code'],
                (int)$session['slot'],
                $session['batch'],
                $session['branch'],
                (int)$session['semester'],
                $time,
                $ip,
            ]
        );
        if ($notified === false) {
            throw new RuntimeException('attendance notification insert failed');
        }
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        if (empty($GLOBALS['qrcas_attendance_last_error'])) {
            $GLOBALS['qrcas_attendance_last_error'] = $e->getMessage();
        }
        return false;
    }
}
