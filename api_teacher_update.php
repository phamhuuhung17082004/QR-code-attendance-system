<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/lib/db.php';
security_start_session();

if (empty($_SESSION['logged']) || ($_SESSION['usertype'] ?? '') !== 'ADMIN') {
    header("location: login.php");
    exit();
}

$enroll = filter_var($_GET['enroll'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$row = $enroll ? db_row('SELECT * FROM teachers WHERE id = ?', 'i', [(int)$enroll]) : null;
if (!$row) {
    $_SESSION['msg'] = '<div class="alert alert-danger mb-2" role="alert">Không tìm thấy bản ghi.</div>';
    header("Location: teacher_details.php");
    exit();
}

require('header.php');
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Giáo viên</li>
            <li class="breadcrumb-item">Chi tiết</li>
            <li class="breadcrumb-item active" aria-current="page">Cập nhật</li>
        </ol>
    </nav>
</div>


<!-- Nội dung chính Bắt đầu -->
<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <form action="api_teacher.php?type=update" method="post">
                    <?php echo csrf_input(); ?>
                    <div class="row mb-3">
                        <label for="inputEmail3" class="col-sm-3 col-form-label">Mã giáo viên</label>
                        <div class="col-sm-9">
                            <input type="text" name="id" value="<?php echo (int)$row['id']; ?>" readonly class="form-control" id="inputEmail3">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Họ và tên giáo viên</label>
                        <div class="col-sm-9">
                            <input type="text" name="teachername" class="form-control" value="<?php echo ui_h((string)$row['name']); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Trình độ học vấn</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="education" value="<?php echo ui_h((string)$row['education']); ?>" required>
                                <option selected="">-- Chọn trình độ --</option>
                                <option value="B.E/B.Tech" <?php if ($row['education'] == 'B.E/B.Tech') echo ' selected="selected"'; ?>>Cử nhân kỹ thuật/công nghệ</option>
                                <option value="M.E/M.Tech" <?php if ($row['education'] == 'M.E/M.Tech') echo ' selected="selected"'; ?>>Thạc sĩ kỹ thuật/công nghệ</option>
                                <option value="Ph.d" <?php if ($row['education'] == 'Ph.d') echo ' selected="selected"'; ?>>Tiến sĩ</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Khoa/Ngành</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="branch" value="<?php echo ui_h((string)$row['branch']); ?>" required>
                                <option selected="">-- Chọn khoa/ngành --</option>
                                <option value="Computer Engineering" <?php if ($row['branch'] == 'Computer Engineering') echo ' selected="selected"'; ?>>Kỹ thuật máy tính</option>
                                <option value="Mechanical Engineering" <?php if ($row['branch'] == 'Mechanical Engineering') echo ' selected="selected"'; ?>>Kỹ thuật cơ khí</option>
                                <option value="Electrical Engineering" <?php if ($row['branch'] == 'Electrical Engineering') echo ' selected="selected"'; ?>>Kỹ thuật điện</option>
                                <option value="Civil Engineering" <?php if ($row['branch'] == 'Civil Engineering') echo ' selected="selected"'; ?>>Kỹ thuật xây dựng</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Chức danh</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="designation" value="<?php echo ui_h((string)$row['designation']); ?>" required>
                                <option selected="">-- Chọn chức danh --</option>
                                <option value="Instructor" <?php if ($row['designation'] == 'Instructor') echo ' selected="selected"'; ?>>Giảng viên</option>
                                <option value="Assistant Professor" <?php if ($row['designation'] == 'Assistant Professor') echo ' selected="selected"'; ?>>Giảng viên trợ giảng</option>
                                <option value="Associate Professor" <?php if ($row['designation'] == 'Associate Professor') echo ' selected="selected"'; ?>>Phó giáo sư</option>
                                <option value="Professor" <?php if ($row['designation'] == 'Professor') echo ' selected="selected"'; ?>>Giáo sư</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Mật khẩu</label>
                        <div class="col-sm-9">
                            <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Để trống nếu không đổi mật khẩu">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính Kết thúc -->


<?php
require('footer.php');
?>
