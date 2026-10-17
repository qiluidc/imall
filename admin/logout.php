<?php
require_once __DIR__ . '/../functions.php';
admin_logout();
header('Location: /admin/login.php');
exit;
