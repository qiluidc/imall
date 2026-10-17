<?php
require_once __DIR__ . '/functions.php';
nocache_headers();

$products = db_all('SELECT * FROM products WHERE status = 1 ORDER BY sort DESC, id DESC');
$siteName = setting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/shop.css'); ?>">
</head>
<body>
    <div class="shop-wrap">
        <header class="shop-header">
            <h1><?php echo e($siteName); ?></h1>
        </header>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>暂无上架商品</p>
                <a href="/admin/login.php" class="btn-primary">进入后台创建商品</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                    <a class="product-card" href="/p.php?slug=<?php echo e($p['slug']); ?>">
                        <div class="card-image" style="background-image:url('<?php echo e(upload_url($p['main_image'])); ?>')"></div>
                        <div class="card-body">
                            <h3><?php echo e($p['title']); ?></h3>
                            <p class="card-subtitle"><?php echo e($p['subtitle']); ?></p>
                            <div class="card-price">
                                <span class="price">¥<?php echo e(format_money($p['price'])); ?></span>
                                <?php if ($p['original_price'] > 0): ?>
                                    <span class="original">¥<?php echo e(format_money($p['original_price'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
