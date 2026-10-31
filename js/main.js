(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Sidebar Toggler
    $('.sidebar-toggler').click(function () {
        $('.sidebar, .content').toggleClass("open");
        return false;
    });

    // Progress Bar
    if ($('.pg-bar').length > 0) {
        $('.pg-bar').waypoint(function () {
            $('.progress .progress-bar').each(function () {
                $(this).css("width", $(this).attr("aria-valuenow") + '%');
            });
        }, {offset: '80%'});
    }

    // Calender
    if ($('#calender').length > 0) {
        $('#calender').datetimepicker({
            inline: true,
            format: 'L'
        });
    }

    // Testimonials carousel
    if ($('.testimonial-carousel').length > 0) {
        $(".testimonial-carousel").owlCarousel({
            autoplay: true,
            smartSpeed: 1000,
            items: 1,
            dots: true,
            loop: true,
            nav : false
        });
    }

    // Worldwide Sales Chart
    if ($("#worldwide-sales").length > 0) {
        var ctx1 = $("#worldwide-sales").get(0).getContext("2d");
        var myChart1 = new Chart(ctx1, {
            type: "bar",
            data: {
                labels: ["2016", "2017", "2018", "2019", "2020", "2021", "2022"],
                datasets: [{
                        label: "USA",
                        data: [15, 30, 55, 65, 60, 80, 95],
                        backgroundColor: "rgba(0, 156, 255, .7)"
                    },
                    {
                        label: "UK",
                        data: [8, 35, 40, 60, 70, 55, 75],
                        backgroundColor: "rgba(0, 156, 255, .5)"
                    },
                    {
                        label: "AU",
                        data: [12, 25, 45, 55, 65, 70, 60],
                        backgroundColor: "rgba(0, 156, 255, .3)"
                    }
                ]
                },
            options: {
                responsive: true
            }
        });
    }

    // Salse & Revenue Chart
    if ($("#salse-revenue").length > 0) {
        var ctx2 = $("#salse-revenue").get(0).getContext("2d");
        var myChart2 = new Chart(ctx2, {
            type: "line",
            data: {
                labels: ["2016", "2017", "2018", "2019", "2020", "2021", "2022"],
                datasets: [{
                        label: "Salse",
                        data: [15, 30, 55, 45, 70, 65, 85],
                        backgroundColor: "rgba(0, 156, 255, .5)",
                        fill: true
                    },
                    {
                        label: "Revenue",
                        data: [99, 135, 170, 130, 190, 180, 270],
                        backgroundColor: "rgba(0, 156, 255, .3)",
                        fill: true
                    }
                ]
                },
            options: {
                responsive: true
            }
        });
    }

    // Single Line Chart
    if ($("#line-chart").length > 0) {
        var ctx3 = $("#line-chart").get(0).getContext("2d");
        var myChart3 = new Chart(ctx3, {
            type: "line",
            data: {
                labels: [50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150],
                datasets: [{
                    label: "Salse",
                    fill: false,
                    backgroundColor: "rgba(0, 156, 255, .3)",
                    data: [7, 8, 8, 9, 9, 9, 10, 11, 14, 14, 15]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    // Single Bar Chart
    if ($("#bar-chart").length > 0) {
        var ctx4 = $("#bar-chart").get(0).getContext("2d");
        var myChart4 = new Chart(ctx4, {
            type: "bar",
            data: {
                labels: ["Italy", "France", "Spain", "USA", "Argentina"],
                datasets: [{
                    backgroundColor: [
                        "rgba(0, 156, 255, .7)",
                        "rgba(0, 156, 255, .6)",
                        "rgba(0, 156, 255, .5)",
                        "rgba(0, 156, 255, .4)",
                        "rgba(0, 156, 255, .3)"
                    ],
                    data: [55, 49, 44, 24, 15]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    // Pie Chart
    if ($("#pie-chart").length > 0) {
        var ctx5 = $("#pie-chart").get(0).getContext("2d");
        var myChart5 = new Chart(ctx5, {
            type: "pie",
            data: {
                labels: ["Italy", "France", "Spain", "USA", "Argentina"],
                datasets: [{
                    backgroundColor: [
                        "rgba(0, 156, 255, .7)",
                        "rgba(0, 156, 255, .6)",
                        "rgba(0, 156, 255, .5)",
                        "rgba(0, 156, 255, .4)",
                        "rgba(0, 156, 255, .3)"
                    ],
                    data: [55, 49, 44, 24, 15]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    // Doughnut Chart
    if ($("#doughnut-chart").length > 0) {
        var ctx6 = $("#doughnut-chart").get(0).getContext("2d");
        var myChart6 = new Chart(ctx6, {
            type: "doughnut",
            data: {
                labels: ["Italy", "France", "Spain", "USA", "Argentina"],
                datasets: [{
                    backgroundColor: [
                        "rgba(0, 156, 255, .7)",
                        "rgba(0, 156, 255, .6)",
                        "rgba(0, 156, 255, .5)",
                        "rgba(0, 156, 255, .4)",
                        "rgba(0, 156, 255, .3)"
                    ],
                    data: [55, 49, 44, 24, 15]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    // Phản hồi khi bấm: gợn sáng rất nhẹ, không cần thư viện hiệu ứng ngoài.
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var interactiveItems = document.querySelectorAll(
            '.content .btn:not(.btn-square):not(.btn-sm-square):not(.btn-lg-square), .auth-submit, .dashboard-action, .dashboard-stat'
        );

        interactiveItems.forEach(function (item) {
            item.classList.add('ui-tap-target');
            item.addEventListener('pointerdown', function (event) {
                var bounds = item.getBoundingClientRect();
                var ripple = document.createElement('span');
                ripple.className = 'ui-ripple';
                ripple.style.left = (event.clientX - bounds.left) + 'px';
                ripple.style.top = (event.clientY - bounds.top) + 'px';
                item.appendChild(ripple);
                ripple.addEventListener('animationend', function () {
                    ripple.remove();
                });
            });
        });
    }
    
})(jQuery);
