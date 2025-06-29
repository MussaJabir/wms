// Toggle Sidebar on Mobile
$(document).ready(function() {
    // Toggle sidebar on mobile
    $('.btn-link').click(function() {
        $('.sidebar').toggleClass('active');
        $('main').toggleClass('active');
    });

    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('.sidebar, .btn-link').length) {
                $('.sidebar').removeClass('active');
                $('main').removeClass('active');
            }
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // Handle form validation
    $('form').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Handle DataTables initialization
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }

    // Handle password confirmation
    $('input[name="confirm_password"]').on('input', function() {
        var password = $('input[name="new_password"]').val();
        var confirm = $(this).val();
        
        if (password !== confirm) {
            $(this).setCustomValidity("Passwords don't match");
        } else {
            $(this).setCustomValidity('');
        }
    });

    // Handle file input preview
    $('input[type="file"]').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Handle numeric input
    $('input[type="number"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Handle phone number formatting
    $('input[type="tel"]').on('input', function() {
        var x = $(this).val().replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        $(this).val(!x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : ''));
    });
}); 