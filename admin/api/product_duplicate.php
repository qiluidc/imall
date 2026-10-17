<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 1, 'msg' => '请求方式错误']);
}

$id = (int) ($_POST['id'] ?? 0);
$src = db_row('SELECT * FROM products WHERE id = ?', [$id]);
if (!$src) {
    json_response(['code' => 1, 'msg' => '商品不存在']);
}

// slug 冲突递增：原slug → 原slug1 → 原slug2 ...（保持小写字母数字-_ 规则）
$base = rtrim((string)$src['slug'], '0123456789');
$slug = $src['slug'] . '1';
$i = 1;
while (db_row('SELECT id FROM products WHERE slug = ?', [$slug])) {
    $i++;
    $slug = $base . $i;
}

$now = now();
$dupTitle = trim((string)$src['title']) . '-草稿';

db_exec('INSERT INTO products (
    slug, title, subtitle, main_image, payment_qr, gallery_images, detail_images, specs,
    price, original_price, stock, status, sort, sales_count, seo_title, seo_desc, created_at, updated_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
    $slug,
    $dupTitle,
    $src['subtitle'],
    $src['main_image'],
    $src['payment_qr'],
    $src['gallery_images'],
    $src['detail_images'],
    $src['specs'],
    $src['price'],
    $src['original_price'],
    $src['stock'],
    0,             // 复制品一律下架，由管理员手动上架
    $src['sort'],
    0,             // 销量不复制，从 0 开始
    $src['seo_title'],
    $src['seo_desc'],
    $now,
    $now,
]);

json_response(['code' => 0, 'msg' => '已复制为草稿（下架状态）', 'data' => ['slug' => $slug]]);
