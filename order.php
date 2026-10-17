<?php
require_once __DIR__ . '/functions.php';
nocache_headers();

$phone = trim($_GET['phone'] ?? '');
$orders = [];
$error = '';

if ($phone !== '') {
    if (!is_mobile($phone)) {
        $error = '请输入正确的手机号';
    } else {
        $orders = db_all('SELECT * FROM orders WHERE buyer_phone = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 50', [$phone]);
    }
}

$siteName = setting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>订单查询 - <?php echo e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/shop.css'); ?>">
</head>
<body>
    <div class="shop-wrap query-page">
        <h1>订单查询</h1>
        <form class="query-form" method="get" action="/order.php">
            <input type="tel" name="phone" placeholder="输入下单手机号" maxlength="11" value="<?php echo e($phone); ?>">
            <button type="submit">查询</button>
        </form>

        <?php if ($error): ?>
            <p class="error-text"><?php echo e($error); ?></p>
        <?php endif; ?>

        <?php if ($phone && empty($error) && empty($orders)): ?>
            <div class="empty-state">
                <p>未找到该手机号的订单</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($orders)): ?>
            <div class="order-list">
                <?php foreach ($orders as $o): ?>
                    <a class="order-item" href="/pay.php?order_no=<?php echo e($o['order_no']); ?>">
                        <div class="order-meta">
                            <span class="order-no"><?php echo e($o['order_no']); ?></span>
                            <span class="order-status <?php echo e(order_status_class((int)$o['status'])); ?>"><?php echo e(order_status_label((int)$o['status'])); ?></span>
                        </div>
                        <div class="order-product">
                            <img src="<?php echo e(upload_url($o['product_image'])); ?>" alt="">
                            <div>
                                <p class="title"><?php echo e($o['product_title']); ?></p>
                                <p class="spec"><?php echo e($o['spec_label']); ?> ×<?php echo e($o['quantity']); ?></p>
                            </div>
                        </div>
                        <div class="order-total">
                            实付：<strong>¥<?php echo e(format_money($o['total_amount'])); ?></strong>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
