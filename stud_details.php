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
        <ol class="breadcrumb mb-0 p1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item">Trang chủ</li>
            <li class="breadcrumb-item">Sinh viên</li>
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
        <button type="button" class="btn btn-outline-primary m-2" data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="fas fa-plus me-2"></i>Thêm sinh viên mới</button>
        <button type="button" class="btn btn-outline-success m-2" data-bs-toggle="modal" data-bs-target="#excelstudModal"><i class="fas fa-plus me-2"></i>Thêm hàng loạt</button>
    </div>

    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">Danh sách sinh viên</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="table">
                        <thead>
                            <tr>
                                <th scope="col">Mã sinh viên</th>
                                <th scope="col">Họ và tên</th>
                                <th scope="col">Học kỳ</th>
                                <th scope="col">Ngành học</th>
                                <th scope="col">Số thứ tự</th>
                                <th scope="col">Lớp</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM students";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $row['enrollment_no']; ?></th>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['semester']; ?></td>
                                    <td><?php echo ui_h(ui_branch_label($row['branch'])); ?></td>
                                    <td><?php echo $row['roll_no']; ?></td>
                                    <td><?php echo $row['batch']; ?></td>
                                    <td>
                                        <button type="button" onclick="updatestud('<?php echo $row['enrollment_no']; ?>')" class="btn btn-square btn-outline-danger btn-sm" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                                        <button type="button" onclick="deletestud('<?php echo $row['enrollment_no']; ?>')" class="btn btn-square btn-outline-success btn-sm" title="Xóa"><i class="fas fa-trash"></i></button>
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


<!-- Modal: Thêm sinh viên mới -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Thêm sinh viên mới</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="col-12">
                    <div class="bg-light rounded h-100 p-4">
                        <form action="api_stud.php?type=add" method="post">
                            <?php echo csrf_input(); ?>
                            <div class="row mb-3">
                                <label for="inputEmail3" class="col-sm-3 col-form-label">Mã sinh viên</label>
                                <div class="col-sm-9">
                                    <input type="text" name="enrollmentno" class="form-control" id="inputEmail3">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Họ và tên</label>
                                <div class="col-sm-9">
                                    <input type="text" name="studentname" class="form-control" id="inputPassword3">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Học kỳ</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="semester" id="floatingSelect" aria-label="Chọn học kỳ" required>
                                        <option selected="">-- Chọn học kỳ --</option>
                                        <option value="1">Học kỳ 1</option>
                                        <option value="2">Học kỳ 2</option>
                                        <option value="3">Học kỳ 3</option>
                                        <option value="4">Học kỳ 4</option>
                                        <option value="5">Học kỳ 5</option>
                                        <option value="6">Học kỳ 6</option>
                                        <option value="7">Học kỳ 7</option>
                                        <option value="8">Học kỳ 8</option>
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
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Số thứ tự</label>
                                <div class="col-sm-9">
                                    <input type="text" name="rollno" class="form-control" id="inputPassword3">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="inputPassword3" class="col-sm-3 col-form-label">Lớp</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="batch" id="floatingSelect" aria-label="Chọn lớp" required>
                                        <option selected="">-- Chọn lớp --</option>
                                        <option value="A1">A1</option>
                                        <option value="A2">A2</option>
                                        <option value="A3">A3</option>
                                        <option value="A4">A4</option>
                                        <option value="A5">A5</option>
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


<!-- Modal: Thêm sinh viên hàng loạt -->
<div class="modal fade" id="excelstudModal" tabindex="-1" aria-labelledby="excelstudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="excelstudModalLabel">Thêm sinh viên hàng loạt</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="col-12">
                    <img src="./img/excelformat.PNG" alt="" class="img-fluid">
                    <p class="text-danger mt-3">* Lưu ý: Vui lòng đảm bảo file Excel được sắp xếp theo đúng thứ tự các cột như hình minh họa phía trên.</p>
                    <div class="bg-light rounded h-100 p-4">
                        <form action="api_stud.php?type=bulk" method="post" enctype="multipart/form-data">
                            <?php echo csrf_input(); ?>
                            <div class="row mb-3">
                                <label for="inputEmail3" class="col-form-label">Chọn file Excel</label>
                                <input type="file" name="file" class="form-control" id="inputEmail3" accept=".xls,.xlsx" required>
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
            form.action = 'api_stud.php?type=delete';
            form.innerHTML = '<input type="hidden" name="enroll"><input type="hidden" name="csrf_token">';
            form.elements.enroll.value = enroll;
            form.elements.csrf_token.value = <?= json_encode(csrf_token()) ?>;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function updatestud(enroll) {
        window.location = `api_stud_update.php?enroll=${enroll}`;
    }
</script>

<?php
require('footer.php');
?>
