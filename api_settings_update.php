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

require('header.php');
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Cài đặt</li>
            <li class="breadcrumb-item active" aria-current="page">Cập nhật</li>
        </ol>
    </nav>
</div>

<!-- Nội dung chính Bắt đầu -->
<div class="container-fluid pt-4 px-4">
    <div class="text-center w-100">
        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>
    </div>

    <?php
    $row = db_row('SELECT * FROM settings WHERE id = 1 LIMIT 1');
    $campusCidrs = trim((string)cfg('campus_cidrs'));
    $currentClientIp = client_ip();
    ?>

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Cập nhật cài đặt hệ thống</h6>
                <form action="api_settings.php?type=update" method="post">
                    <?= csrf_input() ?>
                    <div class="row mb-3">
                        <label for="location" class="col-sm-3 col-form-label">Địa điểm</label>
                        <div class="col-sm-9">
                            <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars((string)($row['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="lat" class="col-sm-3 col-form-label">Vĩ độ</label>
                        <div class="col-sm-9">
                            <input type="text" id="lat" name="lat" class="form-control" value="<?= htmlspecialchars((string)($row['lat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="lon" class="col-sm-3 col-form-label">Kinh độ</label>
                        <div class="col-sm-9">
                            <input type="text" id="lon" name="lon" class="form-control" value="<?= htmlspecialchars((string)($row['lon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="covarage" class="col-sm-3 col-form-label">Bán kính phủ sóng</label>
                        <div class="col-sm-9">
                            <input type="text" id="covarage" name="covarage" class="form-control" value="<?= htmlspecialchars((string)($row['covarage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="text-success fs-6 d-block">*Lưu ý: Phạm vi khu vực cho phép sinh viên thực hiện điểm danh.</span>
                            <span class="text-success fs-6 d-block">*Lưu ý: Ví dụ: 0.5 KM, 1.0 KM, 5.0 KM</span>
                            <span class="text-success fs-6 d-block">*Lưu ý: Chỉ nhập số, không nhập đơn vị KM.</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="campus_cidrs" class="col-sm-3 col-form-label">Dải IP Wi-Fi của trường (CIDR)</label>
                        <div class="col-sm-9">
                            <input type="text" id="campus_cidrs" name="campus_cidrs" class="form-control" value="<?= htmlspecialchars($campusCidrs, ENT_QUOTES, 'UTF-8') ?>" maxlength="255">
                            <span class="text-muted fs-6 d-block mt-1">IP hiện tại của bạn: <strong><?= htmlspecialchars($currentClientIp, ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <?php if ($campusCidrs === ''): ?>
                                <span class="text-warning fs-6 d-block mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Chưa cấu hình: hệ thống KHÔNG kiểm tra IP trường</span>
                            <?php endif; ?>
                            <span class="text-muted fs-6 d-block mt-1">*Lưu ý: Nhập các dải CIDR cách nhau bằng dấu phẩy (tối đa 255 ký tự). Ví dụ: 203.0.113.0/24, 2001:db8::/48</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính Bắt đầu -->

<?php
require('footer.php');
?>
