<?php
require_once __DIR__ . '/../../functions.php';
require_admin();

$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$keyword = trim($_GET['keyword'] ?? '');
$productId = (int)($_GET['product_id'] ?? 0);

$where = 'deleted_at IS NULL';
$params = [];
if ($productId) {
    $where .= ' AND product_id = ?';
    $params[] = $productId;
}
if ($status !== null) {
    $where .= ' AND status = ?';
    $params[] = $status;
}
if ($keyword !== '') {
    $where .= ' AND (order_no LIKE ? OR buyer_phone LIKE ? OR buyer_name LIKE ? OR product_title LIKE ?)';
    $like = '%' . $keyword . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$orders = db_all('SELECT * FROM orders WHERE ' . $where . ' ORDER BY created_at DESC', $params);

$statusLabels = [
    0  => '待付款',
    1  => '已付款',
    2  => '已发货',
    3  => '已完成',
    -1 => '已取消',
];

$headers = ['订单号', '商品', '规格', '数量', '收货人', '手机号', '收货地址', '金额(元)', '状态', '下单时间', '快递公司', '快递单号'];

$filename = 'orders_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
header('Cache-Control: no-store, no-cache');

// UTF-8 BOM，保证 Excel 正确识别中文
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');
fputcsv($fp, $headers);

foreach ($orders as $o) {
    $spec = $o['spec_label'] ?: '默认';
    $row = [
        $o['order_no'],
        $o['product_title'],
        $spec,
        $o['quantity'],
        $o['buyer_name'],
        $o['buyer_phone'],
        $o['buyer_address'],
        $o['total_amount'],
        $statusLabels[(int)$o['status']] ?? $o['status'],
        date('Y-m-d H:i', $o['created_at']),
        $o['tracking_company'] ?: '',
        $o['tracking_no'] ?: '',
    ];
    fputcsv($fp, $row);
}
fclose($fp);
exit;
