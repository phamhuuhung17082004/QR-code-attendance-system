<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'STUDENT') {
    $_SESSION = [];
    header('Location: login.php');
    exit();
}

$enrollment_no = (int)$_SESSION['enrollment_no'];
$row = db_row('SELECT * FROM `students` WHERE `enrollment_no` = ? LIMIT 1', 'i', [$enrollment_no]);
if (!$row) {
    header('Location: login.php');
    exit();
}

require('header.php');
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item active" aria-current="page">Hồ sơ cá nhân</li>
        </ol>
    </nav>
</div>

<!-- Nội dung chính bắt đầu -->
<div class="container-fluid pt-4 px-4">
    <div class="text-center w-100">
        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>
    </div>

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Thông tin sinh viên</h6>
                <form action="api_self_stud_update.php" method="post" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <div class="row mb-3">
                        <label for="inputEmail3" class="col-sm-3 col-form-label">Ảnh đại diện</label>
                        <div id="piceditbox" class="col-sm-9" hidden>
                            <input type="file" name="pic" class="form-control" accept="image/jpeg,image/png,image/jpg">
                        </div>
                        <div id="picshow" class="col-sm-9">
                            <img src="./img/profile/<?php echo htmlspecialchars((string)$row['pic'], ENT_QUOTES, 'UTF-8'); ?>" alt="Chưa có ảnh" width="150px" height="150px" class="rounded border">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputEmail3" class="col-sm-3 col-form-label">Mã sinh viên</label>
                        <div class="col-sm-9">
                            <input type="text" name="enrollmentno" class="form-control" value="<?php echo htmlspecialchars((string)$enrollment_no, ENT_QUOTES, 'UTF-8'); ?>" id="inputEmail3" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="studentname" class="col-sm-3 col-form-label">Họ và tên</label>
                        <div class="col-sm-9">
                            <input type="text" id="studentname" name="studentname" value="<?php echo htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" readonly required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="semester" class="col-sm-3 col-form-label">Học kỳ</label>
                        <div class="col-sm-9">
                            <select class="form-select" id="semester" name="semester" disabled>
                                <option>-- Chọn học kỳ --</option>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($row['semester'] == $s) ? 'selected' : '' ?>>Học kỳ <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="branch" class="col-sm-3 col-form-label">Ngành học</label>
                        <div class="col-sm-9">
                            <select class="form-select" id="branch" name="branch" disabled>
                                <option>-- Chọn ngành --</option>
                                <option value="Computer Engineering" <?= ($row['branch'] === 'Computer Engineering') ? 'selected' : '' ?>>Kỹ thuật Máy tính</option>
                                <option value="Mechanical Engineering" <?= ($row['branch'] === 'Mechanical Engineering') ? 'selected' : '' ?>>Kỹ thuật Cơ khí</option>
                                <option value="Electrical Engineering" <?= ($row['branch'] === 'Electrical Engineering') ? 'selected' : '' ?>>Kỹ thuật Điện</option>
                                <option value="Civil Engineering" <?= ($row['branch'] === 'Civil Engineering') ? 'selected' : '' ?>>Kỹ thuật Xây dựng</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="rollno" class="col-sm-3 col-form-label">Số thứ tự</label>
                        <div class="col-sm-9">
                            <input type="text" id="rollno" name="rollno" class="form-control" value="<?php echo htmlspecialchars((string)$row['roll_no'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="batch" class="col-sm-3 col-form-label">Lớp</label>
                        <div class="col-sm-9">
                            <select class="form-select" id="batch" name="batch" disabled>
                                <option>-- Chọn lớp --</option>
                                <?php foreach (['A1', 'A2', 'A3', 'A4', 'A5'] as $b): ?>
                                    <option value="<?= $b ?>" <?= ($row['batch'] === $b) ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <button type="button" class="btn btn-secondary w-100 m-2" onclick="edit()">Chỉnh sửa thông tin</button>
                        <button type="submit" class="btn btn-primary w-100 m-2" id="formbtn" hidden>Cập nhật</button>
                    </div>
                </form>

                <hr class="my-4">
                <h6 class="mb-3"><i class="fas fa-shield-alt text-primary me-2"></i>Xác thực sinh trắc học & Thiết bị điểm danh</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card p-3 border h-100">
                            <h6><i class="fas fa-user-check text-success me-2"></i>Mẫu khuôn mặt</h6>
                            <p class="small text-muted mb-3">Đăng ký khuôn mặt mẫu dùng để xác minh khi quét QR điểm danh.</p>
                            <a href="face_enrollment.php" class="btn btn-outline-primary mt-auto">
                                <i class="fas fa-camera me-2"></i>Đăng ký khuôn mặt
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 border h-100">
                            <h6><i class="fas fa-fingerprint text-info me-2"></i>Thiết bị điểm danh (Passkey)</h6>
                            <p class="small text-muted mb-3">Gắn thiết bị cá nhân (vân tay/FaceID/PIN) để điểm danh bảo mật chống gian lận.</p>
                            <a href="passkey_enrollment.php" class="btn btn-outline-success mt-auto">
                                <i class="fas fa-mobile-alt me-2"></i>Đăng ký thiết bị (Passkey)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính kết thúc -->

<script>
    function edit() {
        $('#piceditbox').removeAttr("hidden");
        $('#studentname').removeAttr("readonly");
        $('#picshow').attr("hidden", true);
        $('#formbtn').removeAttr("hidden");
    }
</script>

<?php
require('footer.php');
?>
