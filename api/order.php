<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

// 从 JSON body 读取
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$productId = (int) ($input['product_id'] ?? 0);
$specKey   = trim($input['spec_key'] ?? '');
$quantity  = max(1, (int) ($input['quantity'] ?? 1));
$name      = trim($input['buyer_name'] ?? '');
$phone     = trim($input['buyer_phone'] ?? '');
$address   = trim($input['buyer_address'] ?? '');
$remark    = trim($input['buyer_remark'] ?? '');

// 校验
if ($productId <= 0) {
    json_response(['code' => 400, 'msg' => '商品参数错误']);
}
if ($quantity <= 0) {
    json_response(['code' => 400, 'msg' => '数量不能小于 1']);
}
if ($name === '' || mb_strlen($name) > 50) {
    json_response(['code' => 400, 'msg' => '请填写正确的收货人姓名']);
}
if (!is_mobile($phone)) {
    json_response(['code' => 400, 'msg' => '请填写正确的手机号码']);
}
if ($address === '' || mb_strlen($address) > 300) {
    json_response(['code' => 400, 'msg' => '请填写详细收货地址']);
}

$product = db_row('SELECT * FROM products WHERE id = ? AND status = 1', [$productId]);
if (!$product) {
    json_response(['code' => 404, 'msg' => '商品不存在或已下架']);
}

$specs = json_decode($product['specs'] ?? '[]', true) ?: [];
$price = (float) $product['price'];
$stock = (int) $product['stock'];
$specLabel = '';

if (!empty($specs)) {
    $found = null;
    foreach ($specs as $s) {
        if ($s['key'] === $specKey) {
            $found = $s;
            break;
        }
    }
    // 兜底：未传规格 key 或仅单规格时，自动采用第一个，避免误报
    if (!$found && ($specKey === '' || count($specs) === 1)) {
        $found = $specs[0];
    }
    if (!$found) {
        json_response(['code' => 400, 'msg' => '请选择正确的规格']);
    }
    $price     = (float) $found['price'];
    $stock     = (int) $found['stock'];
    $specLabel = $found['label'];
} else {
    $specKey   = 'default';
    $specLabel = '默认';
}

if ($quantity > $stock) {
    json_response(['code' => 400, 'msg' => '库存不足，当前剩余 ' . $stock . ' 件']);
}

$total = round($price * $quantity, 2);
$orderNo = generate_order_no();
$now = now();

try {
    db_exec('INSERT INTO orders (
        order_no, product_id, product_title, product_image, spec_key, spec_label,
        price, quantity, total_amount, buyer_name, buyer_phone, buyer_address, buyer_remark,
        status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)', [
        $orderNo,
        $product['id'],
        $product['title'],
        $product['main_image'],
        $specKey,
        $specLabel,
        $price,
        $quantity,
        $total,
        $name,
        $phone,
        $address,
        $remark,
        $now,
        $now,
    ]);

    // 新订单通知管理员（短信 + 邮件，非阻塞，失败不影响下单结果）
    notify_admin_new_order([
        'order_no'      => $orderNo,
        'product_title' => $product['title'],
        'spec_label'    => $specLabel,
        'quantity'      => $quantity,
        'total_amount'  => $total,
        'buyer_name'    => $name,
        'buyer_phone'   => $phone,
        'buyer_address' => $address,
        'buyer_remark'  => $remark,
        'created_at'    => $now,
    ]);

    json_response([
        'code'      => 0,
        'msg'       => '下单成功',
        'data'      => [
            'order_no'     => $orderNo,
            'total_amount' => $total,
            'pay_url'      => '/pay.php?order_no=' . $orderNo,
        ],
    ]);
} catch (PDOException $e) {
    error_log('Order create failed: ' . $e->getMessage());
    json_response(['code' => 500, 'msg' => '订单创建失败，请稍后重试']);
}
