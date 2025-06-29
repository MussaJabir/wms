<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

$flash = getFlashMessage();
if ($flash): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '<?= $flash['type'] === 'error' ? 'Error' : ($flash['type'] === 'success' ? 'Success' : 'Info') ?>',
                text: '<?= addslashes($flash['message']) ?>',
                icon: '<?= $flash['type'] ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
                timer: <?= $flash['type'] === 'success' ? '3000' : '5000' ?>,
                timerProgressBar: true
            });
        });
    </script>
<?php endif; ?> 