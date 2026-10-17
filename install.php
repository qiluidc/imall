<?php
require_once __DIR__ . '/functions.php';

/**
 * 安装脚本：创建数据表与默认设置
 * 运行一次后建议删除或重命名本文件
 */

$errors = [];
try {
    $pdo = db();
} catch (Throwable $e) {
    $errors[] = '数据库连接失败，请检查 config.php 中的数据库配置。';
}

if (empty($errors)) {
    $sqls = [];

    $sqls[] = "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(64) NOT NULL COMMENT 'URL 标识',
        `title` VARCHAR(255) NOT NULL COMMENT '商品标题',
        `subtitle` VARCHAR(255) DEFAULT NULL COMMENT '副标题',
        `main_image` VARCHAR(255) DEFAULT NULL COMMENT '主图',
        `payment_qr` VARCHAR(255) DEFAULT NULL COMMENT '商品收款码（留空用全局兜底）',
        `gallery_images` TEXT DEFAULT NULL COMMENT '图集 JSON 数组',
        `detail_images` TEXT DEFAULT NULL COMMENT '详情图 JSON 数组',
        `specs` TEXT DEFAULT NULL COMMENT '规格 JSON 数组',
        `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
        `original_price` DECIMAL(10,2) DEFAULT '0.00' COMMENT '划线价',
        `stock` INT(11) NOT NULL DEFAULT '0' COMMENT '库存',
        `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '0下架 1上架',
        `sort` INT(11) NOT NULL DEFAULT '0' COMMENT '排序',
        `sales_count` INT(11) NOT NULL DEFAULT '0' COMMENT '销量',
        `seo_title` VARCHAR(255) DEFAULT NULL,
        `seo_desc` VARCHAR(500) DEFAULT NULL,
        `created_at` INT(11) NOT NULL DEFAULT '0',
        `updated_at` INT(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `status_sort` (`status`, `sort`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';";

    $sqls[] = "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_no` VARCHAR(32) NOT NULL COMMENT '订单号',
        `product_id` INT(11) UNSIGNED NOT NULL,
        `product_title` VARCHAR(255) NOT NULL,
        `product_image` VARCHAR(255) DEFAULT NULL,
        `spec_key` VARCHAR(64) DEFAULT NULL COMMENT '规格键',
        `spec_label` VARCHAR(255) DEFAULT NULL COMMENT '规格名称',
        `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        `quantity` INT(11) NOT NULL DEFAULT '1',
        `total_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        `buyer_name` VARCHAR(100) NOT NULL COMMENT '收货人',
        `buyer_phone` VARCHAR(20) NOT NULL COMMENT '手机号',
        `buyer_address` TEXT NOT NULL COMMENT '详细地址',
        `buyer_remark` TEXT DEFAULT NULL COMMENT '买家备注',
        `status` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '-1取消 0待付款 1已付款 2已发货 3已完成',
        `tracking_company` VARCHAR(100) DEFAULT NULL COMMENT '快递公司',
        `tracking_no` VARCHAR(100) DEFAULT NULL COMMENT '快递单号',
        `paid_at` INT(11) DEFAULT '0',
        `shipped_at` INT(11) DEFAULT '0',
        `created_at` INT(11) NOT NULL DEFAULT '0',
        `updated_at` INT(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_no` (`order_no`),
        KEY `status_created` (`status`, `created_at`),
        KEY `buyer_phone` (`buyer_phone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';";

    $sqls[] = "CREATE TABLE IF NOT EXISTS `settings` (
        `key` VARCHAR(64) NOT NULL COMMENT '配置键',
        `value` TEXT DEFAULT NULL COMMENT '配置值',
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置';";

    foreach ($sqls as $sql) {
        $pdo->exec($sql);
    }

    // 默认设置
    $defaults = [
        'site_name'        => 'iMall 单品商城',
        'site_notice'      => '',
        'payment_qr'       => '',
        'payment_tip'      => '请长按二维码识别，支付订单金额。支付完成后请联系客服或等待后台确认。',
        'contact_phone'    => '',
        'contact_wechat'   => '',
        'contact_wechat_qr'=> '',
    ];
    foreach ($defaults as $k => $v) {
        $exists = db_one('SELECT 1 FROM settings WHERE `key` = ?', [$k]);
        if (!$exists) {
            db_exec('INSERT INTO settings (`key`, `value`) VALUES (?, ?)', [$k, $v]);
        }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - <?php echo e(APP_NAME); ?></title>
    <style>
        body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; background:#f5f7fa; padding:40px 16px; margin:0; }
        .box { max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,.06); }
        h1 { margin:0 0 8px; font-size:24px; }
        .success { color:#16a34a; }
        .error { color:#dc2626; }
        ul { line-height:1.8; }
        .btn { display:inline-block; margin-top:16px; padding:10px 20px; background:#111827; color:#fff; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="box">
        <h1>安装结果</h1>
        <?php if (!empty($errors)): ?>
            <p class="error"><?php echo implode('<br>', $errors); ?></p>
        <?php else: ?>
            <p class="success">数据表与默认配置已创建成功。</p>
            <ul>
                <li>后台地址：<a href="/admin/login.php">/admin/login.php</a></li>
                <li>默认账号：<?php echo e(ADMIN_USERNAME); ?></li>
                <li>默认密码：<code>CJrUPP3UWMgd</code></li>
            </ul>
            <p style="color:#dc2626;"><strong>安全提醒：</strong>安装完成后请删除或重命名本 install.php 文件，并尽快在后台修改默认密码。</p>
            <a class="btn" href="/admin/login.php">进入后台</a>
        <?php endif; ?>
    </div>
</body>
</html>
