<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 1, 'msg' => '请求方式错误']);
}

$id = (int) ($_POST['id'] ?? 0);
$product = db_row('SELECT id, status FROM products WHERE id = ?', [$id]);
if (!$product) {
    json_response(['code' => 1, 'msg' => '商品不存在']);
}

$newStatus = $product['status'] == 1 ? 0 : 1;
db_exec('UPDATE products SET status = ?, updated_at = ? WHERE id = ?', [$newStatus, now(), $id]);

json_response([
    'code' => 0,
    'msg'  => $newStatus == 1 ? '已上架' : '已下架',
    'data' => ['status' => $newStatus],
]);
