<?php
require 'conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/login_handler.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hệ thống điểm danh QR cho giảng viên và sinh viên.">
    <title>Đăng nhập | Hệ thống điểm danh QR</title>
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div id="spinner" hidden>
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Đang tải...</span></div>
    </div>

    <main class="auth-layout">
        <section class="auth-showcase" aria-labelledby="auth-showcase-title">
            <a class="auth-brand" href="login.php" aria-label="Trang đăng nhập HUS QRCAS">
                <span class="auth-brand__school"><img src="img/hus-logo.jpg" alt="HUS - Trường Đại học Khoa học Tự nhiên"></span>
                <span class="auth-brand__system"><i class="fas fa-qrcode"></i> QRCAS</span>
            </a>
            <div class="auth-showcase__copy">
                <span class="auth-showcase__eyebrow"><i class="fas fa-university"></i> Không gian học tập số</span>
                <h1 id="auth-showcase-title">Điểm danh nhanh,<br>vào lớp nhẹ nhàng.</h1>
                <p>Một nơi để quản lý lịch học, mã QR và xác minh điểm danh cho giảng viên, sinh viên.</p>
                <div class="auth-showcase__points">
                    <span><i class="fas fa-check"></i> Quét QR theo phiên học</span>
                    <span><i class="fas fa-check"></i> Hỗ trợ xác minh khuôn mặt</span>
                </div>
            </div>
            <div class="auth-academic-3d" aria-hidden="true">
                <div class="auth-visual__glow"></div>
                <div class="auth-visual__campus"><i class="fas fa-university"></i></div>
                <div class="auth-visual__qr"><i class="fas fa-qrcode"></i></div>
                <div class="auth-visual__card auth-visual__card--top"><i class="fas fa-mobile-alt"></i><span>Mở camera<br><strong>Quét mã QR</strong></span></div>
                <div class="auth-visual__card auth-visual__card--bottom"><i class="fas fa-check-circle"></i><span>Điểm danh<br><strong>Đã xác nhận</strong></span></div>
            </div>
            <p class="auth-showcase__note">Thiết kế theo tinh thần học thuật hiện đại, phù hợp không gian HUS.</p>
        </section>

        <section class="auth-panel" aria-labelledby="login-title">
            <div class="auth-panel__inner">
                <div class="auth-mobile-brand"><img src="img/hus-logo.jpg" alt="HUS"> <span><i class="fas fa-qrcode"></i> QRCAS</span></div>
                <header class="auth-card__header">
                    <span class="auth-card__eyebrow">CHÀO MỪNG TRỞ LẠI</span>
                    <h2 id="login-title">Đăng nhập tài khoản</h2>
                    <p>Chọn đúng vai trò trước khi đăng nhập.</p>
                </header>

                <?php
                if (isset($_SESSION['msg'])) {
                    echo $_SESSION['msg'];
                    unset($_SESSION['msg']);
                }
                ?>

                <div class="auth-role-picker" role="radiogroup" aria-label="Chọn vai trò">
                    <input type="radio" name="login_role" id="role_admin" value="admin" checked>
                    <label for="role_admin"><i class="fas fa-user-shield"></i><span>Quản trị</span></label>
                    <input type="radio" name="login_role" id="role_teacher" value="teacher">
                    <label for="role_teacher"><i class="fas fa-chalkboard-teacher"></i><span>Giảng viên</span></label>
                    <input type="radio" name="login_role" id="role_student" value="student">
                    <label for="role_student"><i class="fas fa-user-graduate"></i><span>Sinh viên</span></label>
                </div>

                <form class="auth-form" id="form_admin" action="login.php?type=admin" method="post" novalidate>
                    <?= csrf_input(); ?>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="admin_username" name="username" placeholder="Tên đăng nhập" autocomplete="username" required>
                        <label for="admin_username">Tên đăng nhập</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="admin_password" name="password" placeholder="Mật khẩu" autocomplete="current-password" required>
                        <label for="admin_password">Mật khẩu</label>
                    </div>
                    <label class="auth-remember"><input type="checkbox" id="rememberAdmin"><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
                    <button type="submit" class="btn btn-primary auth-submit">Đăng nhập <i class="fa fa-arrow-right ms-2"></i></button>
                </form>

                <form class="auth-form" id="form_teacher" action="login.php?type=teacher" method="post" hidden novalidate>
                    <?= csrf_input(); ?>
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="teacher_id" name="teacherid" placeholder="Mã giảng viên" inputmode="numeric" required>
                        <label for="teacher_id">Mã giảng viên</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="teacher_password" name="password" placeholder="Mật khẩu" autocomplete="current-password" required>
                        <label for="teacher_password">Mật khẩu</label>
                    </div>
                    <label class="auth-remember"><input type="checkbox" id="rememberTeacher"><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
                    <button type="submit" class="btn btn-primary auth-submit">Đăng nhập <i class="fa fa-arrow-right ms-2"></i></button>
                </form>

                <form class="auth-form" id="form_student" action="login.php?type=student" method="post" hidden novalidate>
                    <?= csrf_input(); ?>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="student_enroll" name="enroll" placeholder="Mã sinh viên" inputmode="numeric" autocomplete="username" required>
                        <label for="student_enroll">Mã sinh viên</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="student_password" name="password" placeholder="Mật khẩu" autocomplete="current-password" required>
                        <label for="student_password">Mật khẩu</label>
                    </div>
                    <label class="auth-remember"><input type="checkbox" id="rememberStudent"><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
                    <button type="submit" class="btn btn-primary auth-submit">Đăng nhập <i class="fa fa-arrow-right ms-2"></i></button>
                </form>

                <p class="auth-help"><i class="fas fa-lock"></i> Thông tin đăng nhập được bảo vệ an toàn.</p>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        (function () {
            const forms = {
                admin: document.getElementById('form_admin'),
                teacher: document.getElementById('form_teacher'),
                student: document.getElementById('form_student')
            };

            document.querySelectorAll('input[name="login_role"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    Object.keys(forms).forEach(function (role) {
                        const active = role === radio.value;
                        forms[role].hidden = !active;
                        forms[role].setAttribute('aria-hidden', active ? 'false' : 'true');
                    });
                });
            });

            document.querySelectorAll('.auth-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    let valid = true;
                    form.querySelectorAll('input[required]').forEach(function (input) {
                        const empty = input.value.trim() === '';
                        input.classList.toggle('is-invalid', empty);
                        valid = valid && !empty;
                    });
                    if (!valid) {
                        event.preventDefault();
                        let alert = form.querySelector('.alert-client');
                        if (!alert) {
                            alert = document.createElement('div');
                            alert.className = 'alert alert-danger alert-client mt-3 mb-0';
                            alert.textContent = 'Vui lòng nhập đầy đủ thông tin.';
                            form.appendChild(alert);
                        }
                    }
                });
                form.querySelectorAll('input').forEach(function (input) {
                    input.addEventListener('input', function () {
                        input.classList.remove('is-invalid');
                        const alert = form.querySelector('.alert-client');
                        if (alert) alert.remove();
                    });
                });
            });
        }());
    </script>
</body>
</html>
