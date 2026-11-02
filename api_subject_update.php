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
            <li class="breadcrumb-item">Môn học</li>
            <li class="breadcrumb-item">Chi tiết</li>
            <li class="breadcrumb-item active" aria-current="page">Cập nhật</li>
        </ol>
    </nav>
</div>


<!-- Nội dung chính Bắt đầu -->
<div class="container-fluid pt-4 px-4">
    <?php
    if (isset($_GET['enroll'])) {
        $enroll = mysqli_escape_string($conn, $_GET['enroll']);
        $sql = "SELECT * FROM subjects WHERE subject_code='$enroll'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
    }
    ?>


    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <form action="api_subject.php?type=update" method="post">
                    <?= csrf_input() ?>
                    <div class="row mb-3">
                        <label for="inputEmail3" class="col-sm-3 col-form-label">Mã môn học</label>
                        <div class="col-sm-9">
                            <input type="number" name="subjectcode" value="<?php echo $row['subject_code']; ?>" readonly class="form-control" id="inputEmail3">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Tên môn học</label>
                        <div class="col-sm-9">
                            <input type="text" name="description" class="form-control" value="<?php echo $row['name']; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Tên viết tắt</label>
                        <div class="col-sm-9">
                            <input type="text" name="abbrevation" class="form-control" value="<?php echo $row['abbreviation']; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Chọn học kỳ</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="semester" value="<?php echo $row['semester']; ?>" required>
                                <option selected="">-- Chọn học kỳ --</option>
                                <option value="1" <?php if ($row['semester'] == '1') echo ' selected="selected"'; ?>>Học kỳ 1</option>
                                <option value="2" <?php if ($row['semester'] == '2') echo ' selected="selected"'; ?>>Học kỳ 2</option>
                                <option value="3" <?php if ($row['semester'] == '3') echo ' selected="selected"'; ?>>Học kỳ 3</option>
                                <option value="4" <?php if ($row['semester'] == '4') echo ' selected="selected"'; ?>>Học kỳ 4</option>
                                <option value="5" <?php if ($row['semester'] == '5') echo ' selected="selected"'; ?>>Học kỳ 5</option>
                                <option value="6" <?php if ($row['semester'] == '6') echo ' selected="selected"'; ?>>Học kỳ 6</option>
                                <option value="7" <?php if ($row['semester'] == '7') echo ' selected="selected"'; ?>>Học kỳ 7</option>
                                <option value="8" <?php if ($row['semester'] == '8') echo ' selected="selected"'; ?>>Học kỳ 8</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Khoa/Ngành</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="branch" value="<?php echo $row['branch']; ?>" required>
                                <option selected="">-- Chọn khoa/ngành --</option>
                                <option value="Computer Engineering" <?php if ($row['branch'] == 'Computer Engineering') echo ' selected="selected"'; ?>>Kỹ thuật máy tính</option>
                                <option value="Mechanical Engineering" <?php if ($row['branch'] == 'Mechanical Engineering') echo ' selected="selected"'; ?>>Kỹ thuật cơ khí</option>
                                <option value="Electrical Engineering" <?php if ($row['branch'] == 'Electrical Engineering') echo ' selected="selected"'; ?>>Kỹ thuật điện</option>
                                <option value="Civil Engineering" <?php if ($row['branch'] == 'Civil Engineering') echo ' selected="selected"'; ?>>Kỹ thuật xây dựng</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="inputPassword3" class="col-sm-3 col-form-label">Giáo viên phụ trách</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="teacherid" value="<?php echo $row['id']; ?>" readonly required>
                                <option selected="">-- Chọn giáo viên --</option>
                                <?php
                                $tsql = "SELECT * FROM teachers";
                                $tresult = mysqli_query($conn, $tsql);
                                while ($trow = mysqli_fetch_assoc($tresult)) {
                                ?>
                                    <option value="<?php echo $trow['id']; ?>" <?php if ($row['teacher_id'] == $trow['id']) echo ' selected="selected"'; ?>><?php echo $trow['name']; ?></option>
                                <?php
                                }
                                ?>
                            </select>
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
