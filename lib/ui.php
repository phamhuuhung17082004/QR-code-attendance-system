<?php
/** Các nhãn hiển thị tiếng Việt, không làm thay đổi dữ liệu đang lưu trong CSDL. */

if (defined('QRCAS_UI_LOADED')) {
    return;
}
define('QRCAS_UI_LOADED', true);

function ui_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ui_branch_label(string $branch): string
{
    $labels = [
        'Computer Engineering' => 'Kỹ thuật máy tính',
        'Civil Engineering' => 'Kỹ thuật xây dựng',
        'Mechanical Engineering' => 'Kỹ thuật cơ khí',
        'Electrical Engineering' => 'Kỹ thuật điện',
    ];
    return $labels[$branch] ?? $branch;
}

function ui_role_label(string $role): string
{
    $labels = [
        'ADMIN' => 'Quản trị viên',
        'TEACHER' => 'Giảng viên',
        'STUDENT' => 'Sinh viên',
    ];
    return $labels[$role] ?? $role;
}

function ui_education_label(string $education): string
{
    $labels = [
        'B.E/B.Tech' => 'Cử nhân kỹ thuật/công nghệ',
        'M.E/M.Tech' => 'Thạc sĩ kỹ thuật/công nghệ',
        'Ph.d' => 'Tiến sĩ',
        'Ph.D' => 'Tiến sĩ',
    ];
    return $labels[$education] ?? $education;
}

function ui_designation_label(string $designation): string
{
    $labels = [
        'Instructor' => 'Giảng viên thực hành',
        'Assistant Professor' => 'Giảng viên',
        'Associate Professor' => 'Phó giáo sư',
        'Professor' => 'Giáo sư',
    ];
    return $labels[$designation] ?? $designation;
}

function ui_days(): array
{
    return [
        'Monday' => 'Thứ Hai',
        'Tuesday' => 'Thứ Ba',
        'Wednesday' => 'Thứ Tư',
        'Thursday' => 'Thứ Năm',
        'Friday' => 'Thứ Sáu',
        'Saturday' => 'Thứ Bảy',
    ];
}

function ui_slot_labels(): array
{
    return [
        1 => 'Tiết 1 (07:00 - 07:50)',
        2 => 'Tiết 2 (07:50 - 08:40)',
        3 => 'Tiết 3 (08:50 - 09:40)',
        4 => 'Tiết 4 (09:40 - 10:30)',
        5 => 'Tiết 5 (10:40 - 11:30)',
        6 => 'Tiết 6 (11:30 - 12:20)',
        7 => 'Tiết 7 (12:30 - 13:20)',
        8 => 'Tiết 8 (13:20 - 14:10)',
        9 => 'Tiết 9 (14:20 - 15:10)',
        10 => 'Tiết 10 (15:10 - 16:00)',
        11 => 'Tiết 11 (16:10 - 17:00)',
        12 => 'Tiết 12 (17:00 - 17:50)',
    ];
}

function ui_academic_years(): array
{
    $year = (int)date('Y');
    $start = ((int)date('n') >= 9) ? $year : $year - 1;
    $years = [];
    for ($offset = -1; $offset <= 3; $offset++) {
        $first = $start + $offset;
        $years[] = $first . '-' . substr((string)($first + 1), -2);
    }
    return $years;
}
