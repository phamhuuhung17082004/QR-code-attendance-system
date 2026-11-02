<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    session_destroy();
    header("location: login.php");
    exit();
}
?>
<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Giảng viên</li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết</li>
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
        <button type="button" class="btn btn-outline-primary m-2" data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="fas fa-plus me-2"></i>Thêm giảng viên mới</button>
    </div>

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Danh sách giảng viên</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="table">
                        <thead>
                            <tr>
                                <th scope="col">Mã giảng viên</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Trình độ học vấn</th>
                                <th scope="col">Chức danh</th>
                                <th scope="col">Ngành học</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM teachers";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                <tr>
                                    <th scope="row"><?php echo (int)$row['id']; ?></th>
                                    <td><?php echo ui_h((string)$row['name']); ?></td>
                                    <td><?php echo ui_h(ui_education_label($row['education'])); ?></td>
                                    <td><?php echo ui_h(ui_designation_label($row['designation'])); ?></td>
                                    <td><?php echo ui_h(ui_branch_label($row['branch'])); ?></td>
                                    <td>
                                        <button type="button" onclick="updatestud(<?php echo (int)$row['id']; ?>)" class="btn btn-square btn-outline-danger btn-sm" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                                        <button type="button" onclick="deletestud(<?php echo (int)$row['id']; ?>)" class="btn btn-square btn-outline-success btn-sm" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Nội dung chính kết thúc -->


<!-- Modal: Thêm giảng viên mới -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Thêm giảng viên mới</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="col-12">
                    <div class="bg-light rounded h-100 p-4">
                        <form action="api_teacher.php?type=add" method="post">
                            <?php echo csrf_input(); ?>
                            <div class="row mb-3">
                                <label for="inputEmail3" class="col-sm-3 col-form-label">Mã giảng viên</label>
                                <div class="col-sm-9">
                                    <input type="text" name="id" value="Mã giảng viên được cấp tự động." readonly class="form-control" id="inputEmail3">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Họ và tên</label>
                                <div class="col-sm-9">
                                    <input type="text" name="teachername" class="form-control" id="inputPassword3">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Trình độ học vấn</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="education" id="floatingSelect" aria-label="Chọn trình độ học vấn" required>
                                        <option selected="">-- Chọn trình độ --</option>
                                        <option value="B.E/B.Tech">Cử nhân kỹ thuật/công nghệ</option>
                                        <option value="M.E/M.Tech">Thạc sĩ kỹ thuật/công nghệ</option>
                                        <option value="Ph.d">Tiến sĩ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Ngành học</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="branch" id="floatingSelect" aria-label="Chọn ngành" required>
                                        <option selected="">-- Chọn ngành --</option>
                                        <option value="Computer Engineering">Kỹ thuật Máy tính</option>
                                        <option value="Mechanical Engineering">Kỹ thuật Cơ khí</option>
                                        <option value="Electrical Engineering">Kỹ thuật Điện</option>
                                        <option value="Civil Engineering">Kỹ thuật Xây dựng</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Chức danh</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="designation" id="floatingSelect" aria-label="Chọn chức danh" required>
                                        <option selected="">-- Chọn chức danh --</option>
                                        <option value="Instructor">Giảng viên thực hành</option>
                                        <option value="Assistant Professor">Giảng viên</option>
                                        <option value="Associate Professor">Phó Giáo sư</option>
                                        <option value="Professor">Giáo sư</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Mật khẩu</label>
                                <div class="col-sm-9">
                                    <input type="password" name="password" class="form-control" id="inputPassword3" minlength="8" required autocomplete="new-password">
                                </div>
                            </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    function deletestud(enroll) {
        let isDelete = confirm('Bạn có chắc chắn muốn xóa không?');
        if (isDelete) {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'api_teacher.php?type=delete';
            form.innerHTML = '<input type="hidden" name="enroll"><input type="hidden" name="csrf_token">';
            form.elements.enroll.value = enroll;
            form.elements.csrf_token.value = <?= json_encode(csrf_token()) ?>;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function updatestud(enroll) {
        window.location = `api_teacher_update.php?enroll=${enroll}`;
    }
</script>

<?php
require('footer.php');
?>
