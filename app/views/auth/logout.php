<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Handle logout
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
$_SESSION = [];
redirect('login');
?> 