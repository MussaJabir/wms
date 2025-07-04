// Payment Methods JavaScript - Phone Payment Only
$(document).ready(function() {
    $("#paymentModal").on("hidden.bs.modal", function() {
        $("#phone_provider").val("");
        $("#phone_provider").prop("required", true);
    });

    $("#paymentModal form").on("submit", function(e) {
        if (!$("#phone_provider").val()) {
            e.preventDefault();
            Swal.fire({
                title: "Validation Error",
                text: "Please select a phone payment provider",
                icon: "error",
                confirmButtonText: "OK"
            });
        }
    });
});
