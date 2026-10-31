 <!-- Footer Start -->
 <div class="container-fluid pt-4 px-4">
     <div class="bg-light rounded-top p-4">
         <div class="row">
             <div class="col-12 col-sm-6 text-center text-sm-start">
                &copy; <a href="#">Hệ thống điểm danh QR</a>. Bảo lưu mọi quyền.
             </div>
         </div>
     </div>
 </div>
 <!-- Footer End -->
 </div>
 <!-- Content End -->


 <!-- Back to Top -->
 <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
 </div>

 <!-- JavaScript Libraries -->
 <!-- <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script> -->

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
 <script src="lib/chart/chart.min.js"></script>
 <script src="lib/easing/easing.min.js"></script>
 <script src="lib/waypoints/waypoints.min.js"></script>
 <script src="lib/owlcarousel/owl.carousel.min.js"></script>
 <script src="lib/tempusdominus/js/moment.min.js"></script>
 <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
 <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

 <!-- Template Javascript -->
 <script src="js/main.js"></script>

 <script>
     const attendanceTable = document.querySelector('#table');
     if (attendanceTable) {
     new DataTable(attendanceTable, {
         layout: {
             topStart: {
                    buttons: [{
                        extend: 'print',
                        text: 'In',
                         exportOptions: {
                             columns: ':visible'
                         }
                     },
                    { extend: 'colvis', text: 'Ẩn/hiện cột' }
                 ]
             }
         },
        language: {
            decimal: '',
            emptyTable: 'Không có dữ liệu trong bảng',
            info: 'Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ dòng',
            infoEmpty: 'Hiển thị 0 đến 0 trong tổng số 0 dòng',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            loadingRecords: 'Đang tải...',
            processing: 'Đang xử lý...',
            search: 'Tìm kiếm:',
            zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' },
            buttons: { colvis: 'Ẩn/hiện cột' }
        },
        columnDefs: [{
             targets: 0,
             visible: false
         }]
     });
     }
 </script>
 </body>

 </html>
