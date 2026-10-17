<?php
require_once __DIR__ . '/../functions.php';
nocache_headers();

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (admin_login($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = '账号或密码错误';
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
</head>
<body class="login-body">
    <div class="login-box">
        <h1><?php echo e(APP_NAME); ?></h1>
        <p>后台管理登录</p>
        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form method="post" action="/admin/login.php">
            <div class="form-group">
                <label>账号</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn-submit">登录</button>
        </form>
    </div>
</body>
</html>
