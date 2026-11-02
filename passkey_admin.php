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

$passkeys = db_all(
    "SELECT p.id, p.enrollment_no, s.name AS student_name, p.label, p.status, p.created_at, p.approved_at, p.last_used_at,
            (SELECT COUNT(*) FROM student_face_profiles f WHERE f.enrollment_no = p.enrollment_no) AS has_face
     FROM student_passkeys p
     LEFT JOIN students s ON p.enrollment_no = s.enrollment_no
     ORDER BY p.id DESC"
);

$pendingFaces = db_all(
    "SELECT f.enrollment_no, s.name AS student_name, f.status, f.created_at, f.updated_at
     FROM student_face_profiles f
     LEFT JOIN students s ON f.enrollment_no = s.enrollment_no
     WHERE f.status = 'pending'
     ORDER BY f.created_at DESC"
);

require __DIR__ . '/header.php';
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item active" aria-current="page">Quản lý thiết bị (Passkey)</li>
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

    <!-- Khối hồ sơ khuôn mặt chờ duyệt Bắt đầu -->
    <div class="row bg-light rounded mx-0 mb-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="mb-0">Danh sách hồ sơ khuôn mặt chờ duyệt</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Mã SV</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Ngày đăng ký</th>
                                <th scope="col" style="min-width: 200px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingFaces)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Không có hồ sơ khuôn mặt nào đang chờ duyệt.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingFaces as $pf): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars((string)$pf['enrollment_no'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars((string)($pf['student_name'] ?? 'Chưa rõ'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-warning text-dark">Chờ duyệt</span></td>
                                        <td><?= htmlspecialchars((string)$pf['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <form method="post" action="api_passkey_admin.php" class="d-inline" onsubmit="return confirm('Xác nhận phê duyệt hồ sơ khuôn mặt của sinh viên <?= htmlspecialchars((string)$pf['enrollment_no'], ENT_QUOTES, 'UTF-8') ?>?');">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="approve_face">
                                                    <input type="hidden" name="enrollment_no" value="<?= (int)$pf['enrollment_no'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt hồ sơ khuôn mặt">
                                                        <i class="fas fa-check me-1"></i>Duyệt
                                                    </button>
                                                </form>

                                                <form method="post" action="api_passkey_admin.php" class="d-inline" onsubmit="return confirm('Xác nhận từ chối/đặt lại mẫu khuôn mặt của sinh viên <?= htmlspecialchars((string)$pf['enrollment_no'], ENT_QUOTES, 'UTF-8') ?>?');">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="delete_face">
                                                    <input type="hidden" name="enrollment_no" value="<?= (int)$pf['enrollment_no'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Từ chối/Đặt lại mẫu khuôn mặt">
                                                        <i class="fas fa-times me-1"></i>Từ chối/Đặt lại
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Khối hồ sơ khuôn mặt chờ duyệt Kết thúc -->

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="mb-0">Danh sách thiết bị Passkey & Khuôn mặt sinh viên</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="table">
                        <thead>
                            <tr>
                                <th scope="col">Mã SV</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Nhãn thiết bị</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Ngày tạo</th>
                                <th scope="col">Lần dùng cuối</th>
                                <th scope="col" style="min-width: 220px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($passkeys)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Chưa có thiết bị passkey nào trong hệ thống.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($passkeys as $pk): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars((string)$pk['enrollment_no'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars((string)($pk['student_name'] ?? 'Chưa rõ'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$pk['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($pk['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Đã duyệt</span>
                                            <?php elseif ($pk['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Đã thu hồi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string)$pk['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($pk['last_used_at']) ? htmlspecialchars((string)$pk['last_used_at'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Chưa dùng</span>' ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if ($pk['status'] === 'pending'): ?>
                                                    <form method="post" action="api_passkey_admin.php" class="d-inline" onsubmit="return confirm('Xác nhận phê duyệt thiết bị này?');">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="id" value="<?= (int)$pk['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt thiết bị">
                                                            <i class="fas fa-check me-1"></i>Duyệt
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($pk['status'] !== 'revoked'): ?>
                                                    <form method="post" action="api_passkey_admin.php" class="d-inline" onsubmit="return confirm('Xác nhận thu hồi quyền sử dụng thiết bị này?');">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="revoke">
                                                        <input type="hidden" name="id" value="<?= (int)$pk['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Thu hồi thiết bị">
                                                            <i class="fas fa-ban me-1"></i>Thu hồi
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="post" action="api_passkey_admin.php" class="d-inline" onsubmit="return confirm('Xác nhận xóa mẫu khuôn mặt của sinh viên <?= htmlspecialchars((string)$pk['enrollment_no'], ENT_QUOTES, 'UTF-8') ?>? Sinh viên sẽ phải đăng ký lại khuôn mặt.');">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="delete_face">
                                                    <input type="hidden" name="enrollment_no" value="<?= (int)$pk['enrollment_no'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Xóa mẫu khuôn mặt đã lưu">
                                                        <i class="fas fa-user-times me-1"></i>Xóa mẫu khuôn mặt
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính Kết thúc -->

<?php require __DIR__ . '/footer.php'; ?>
