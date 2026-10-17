<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$id = (int) ($input['id'] ?? 0);
$slug = trim($input['slug'] ?? '');
$title = trim($input['title'] ?? '');
$subtitle = trim($input['subtitle'] ?? '');
$mainImage = trim($input['main_image'] ?? '');
$paymentQr = trim($input['payment_qr'] ?? '');
$galleryImages = $input['gallery_images'] ?? [];
$detailImages = $input['detail_images'] ?? [];
$specs = $input['specs'] ?? [];
$price = (float) ($input['price'] ?? 0);
$originalPrice = (float) ($input['original_price'] ?? 0);
$stock = (int) ($input['stock'] ?? 0);
$status = (int) ($input['status'] ?? 1);
$sort = (int) ($input['sort'] ?? 0);
$seoTitle = trim($input['seo_title'] ?? '');
$seoDesc = trim($input['seo_desc'] ?? '');

if ($title === '' || $slug === '') {
    json_response(['code' => 400, 'msg' => '标题和 URL 标识不能为空']);
}
if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
    json_response(['code' => 400, 'msg' => 'URL 标识只能包含小写字母、数字、下划线和连字符']);
}
if ($mainImage === '') {
    json_response(['code' => 400, 'msg' => '请上传商品主图']);
}
if ($price < 0) {
    json_response(['code' => 400, 'msg' => '售价不能小于 0']);
}

$detailImages = is_array($detailImages) ? $detailImages : [];
$galleryImages = is_array($galleryImages) ? $galleryImages : [];
$specs = is_array($specs) ? $specs : [];

// 处理规格：校验并生成 key
$cleanSpecs = [];
foreach ($specs as $s) {
    $label = trim($s['label'] ?? '');
    $key = trim($s['key'] ?? '');
    $specPrice = (float) ($s['price'] ?? 0);
    $specStock = (int) ($s['stock'] ?? 0);
    if ($label === '' || $key === '') continue;
    if (!preg_match('/^[a-z0-9_-]+$/', $key)) {
        json_response(['code' => 400, 'msg' => '规格标识只能包含小写字母、数字、下划线和连字符: ' . $key]);
    }
    $cleanSpecs[] = ['key' => $key, 'label' => $label, 'price' => $specPrice, 'stock' => $specStock];
}

// 检查 slug 唯一性
$exist = db_row('SELECT id FROM products WHERE slug = ? AND id != ?', [$slug, $id]);
if ($exist) {
    json_response(['code' => 400, 'msg' => 'URL 标识已存在']);
}

$now = now();
$detailJson = json_encode($detailImages, JSON_UNESCAPED_UNICODE);
$galleryJson = json_encode($galleryImages, JSON_UNESCAPED_UNICODE);
$specJson = json_encode($cleanSpecs, JSON_UNESCAPED_UNICODE);

try {
    if ($id > 0) {
        db_exec('UPDATE products SET
            slug=?, title=?, subtitle=?, main_image=?, payment_qr=?, gallery_images=?, detail_images=?, specs=?,
            price=?, original_price=?, stock=?, status=?, sort=?, seo_title=?, seo_desc=?, updated_at=?
            WHERE id=?', [
            $slug, $title, $subtitle, $mainImage, $paymentQr, $galleryJson, $detailJson, $specJson,
            $price, $originalPrice, $stock, $status, $sort, $seoTitle, $seoDesc, $now, $id,
        ]);
    } else {
        db_exec('INSERT INTO products (
            slug, title, subtitle, main_image, payment_qr, gallery_images, detail_images, specs,
            price, original_price, stock, status, sort, sales_count, seo_title, seo_desc, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)', [
            $slug, $title, $subtitle, $mainImage, $paymentQr, $galleryJson, $detailJson, $specJson,
            $price, $originalPrice, $stock, $status, $sort, $seoTitle, $seoDesc, $now, $now,
        ]);
        $id = (int) db_last_id();
    }
    json_response(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id, 'slug' => $slug]]);
} catch (PDOException $e) {
    error_log('Product save failed: ' . $e->getMessage());
    json_response(['code' => 500, 'msg' => '保存失败，请稍后重试']);
}
