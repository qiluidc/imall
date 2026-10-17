<?php
require_once __DIR__ . '/../functions.php';
nocache_headers();

// 启动会话并校验登录
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$publicPages = ['/admin/login.php'];
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!in_array($current, $publicPages, true) && !is_admin()) {
    header('Location: /admin/login.php');
    exit;
}

function admin_active(string $path): string
{
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}

function admin_page_start(string $title): void
{
    $siteName = setting('site_name', APP_NAME);
    $adminUser = $_SESSION['admin_user'] ?? ADMIN_USERNAME;
    ?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> - <?php echo e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand"><?php echo e($siteName); ?></div>
        <nav>
            <a class="<?php echo admin_active('/admin/index.php'); ?>" href="/admin/index.php">概览</a>
            <a class="<?php echo admin_active('/admin/products.php') . admin_active('/admin/product_edit.php'); ?>" href="/admin/products.php">商品管理</a>
            <a class="<?php echo admin_active('/admin/orders.php') . admin_active('/admin/order_view.php'); ?>" href="/admin/orders.php">订单管理</a>
            <a class="<?php echo admin_active('/admin/settings.php'); ?>" href="/admin/settings.php">系统设置</a>
            <a class="<?php echo admin_active('/admin/notify.php'); ?>" href="/admin/notify.php">通知设置</a>
        </nav>
        <div class="user-bar">
            <span><?php echo e($adminUser); ?></span>
            <a href="/admin/logout.php">退出</a>
        </div>
    </aside>
    <main class="admin-main">
<?php
}

function admin_page_end(): void
{
    ?>
    </main>
</div>
<script src="<?php echo asset('js/admin.js'); ?>"></script>
</body>
</html>
<?php
}

function flash_set(string $type, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function flash_html(): string
{
    $flash = flash_get();
    if (!$flash) return '';
    return '<div class="flash flash-' . e($flash['type']) . '">' . e($flash['msg']) . '</div>';
}
