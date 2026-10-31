<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/security.php';

security_start_session();
if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header('Location: login.php');
    exit();
}

$attendanceTestMode = (string)cfg('attendance_test_mode') === '1';
require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Cài đặt</li>
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

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Cài đặt hệ thống</h6>
                <section class="test-mode-card<?= $attendanceTestMode ? ' test-mode-card--active' : '' ?> mb-4">
                    <div>
                        <span class="badge <?= $attendanceTestMode ? 'bg-warning text-dark' : 'bg-secondary' ?> mb-2">
                            <?= $attendanceTestMode ? 'ĐANG BẬT' : 'ĐANG TẮT' ?>
                        </span>
                        <h2 class="h6 mb-1">Chế độ kiểm thử điểm danh</h2>
                        <p class="mb-0">Khi bật, chỉ bỏ kiểm tra giờ tiết để bạn test. QR động, GPS, khuôn mặt và chống điểm danh trùng vẫn bắt buộc.</p>
                    </div>
                    <form method="post" action="api_test_mode.php" class="test-mode-card__action">
                        <?= csrf_input() ?>
                        <input type="hidden" name="enabled" value="<?= $attendanceTestMode ? '0' : '1' ?>">
                        <button type="submit" class="btn <?= $attendanceTestMode ? 'btn-outline-danger' : 'btn-warning' ?>">
                            <i class="fas <?= $attendanceTestMode ? 'fa-power-off' : 'fa-flask' ?> me-2"></i>
                            <?= $attendanceTestMode ? 'Tắt chế độ kiểm thử' : 'Bật chế độ kiểm thử' ?>
                        </button>
                    </form>
                </section>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Địa điểm trường</th>
                                <th scope="col">Vĩ độ</th>
                                <th scope="col">Kinh độ</th>
                                <th scope="col">Bán kính phủ sóng</th>
                                <th scope="col">Dải IP Wi-Fi trường (CIDR)</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $settingrow = db_row("SELECT * FROM `settings` WHERE `id` = 1 LIMIT 1");
                            $campusCidrs = trim((string)cfg('campus_cidrs'));
                            $currentClientIp = client_ip();
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($settingrow['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($settingrow['lat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($settingrow['lon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($settingrow['covarage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> KM</td>
                                <td>
                                    <?php if ($campusCidrs !== ''): ?>
                                        <code><?= htmlspecialchars($campusCidrs, ENT_QUOTES, 'UTF-8') ?></code>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Chưa cấu hình: hệ thống KHÔNG kiểm tra IP trường</span>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">IP hiện tại của bạn: <strong><?= htmlspecialchars($currentClientIp, ENT_QUOTES, 'UTF-8') ?></strong></div>
                                </td>
                                <td><button type="button" onclick="updateSetting()" class="btn btn-square btn-outline-danger btn-sm" title="Chỉnh sửa cài đặt"><i class="fas fa-edit"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính Kết thúc -->

<script>
    function updateSetting() {
        window.location = `api_settings_update.php`;
    }
</script>

<?php
require('footer.php');
?>
