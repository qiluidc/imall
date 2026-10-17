<?php
require_once __DIR__ . '/../../admin/common.php';
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('error', '请求方式错误');
    header('Location: /admin/orders.php');
    exit;
}

$action = $_POST['action'] ?? '';
$ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
$id = (int) ($_POST['id'] ?? 0);
if (!$ids && $id) $ids = [$id];
$ids = array_values(array_filter($ids));

$now = now();
$referer = $_SERVER['HTTP_REFERER'] ?? '/admin/orders.php';

// 批量操作：移入回收站 / 恢复 / 彻底删除（彻底删除仅限已在回收站的订单，安全兜底）
if (in_array($action, ['trash', 'restore', 'destroy'], true)) {
    if (!$ids) {
        flash_set('error', '未选择订单');
        header('Location: ' . $referer);
        exit;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    if ($action === 'trash') {
        db_exec("UPDATE orders SET deleted_at=?, updated_at=? WHERE id IN($in) AND deleted_at IS NULL", array_merge([$now, $now], $ids));
        flash_set('success', '已移入回收站（' . count($ids) . ' 单）');
    } elseif ($action === 'restore') {
        db_exec("UPDATE orders SET deleted_at=NULL, updated_at=? WHERE id IN($in) AND deleted_at IS NOT NULL", array_merge([$now], $ids));
        flash_set('success', '已从回收站恢复（' . count($ids) . ' 单）');
    } else { // destroy
        db_exec("DELETE FROM orders WHERE id IN($in) AND deleted_at IS NOT NULL", $ids);
        flash_set('success', '已彻底删除（' . count($ids) . ' 单）');
    }
    header('Location: ' . $referer);
    exit;
}

// 单条操作
$order = db_row('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) {
    flash_set('error', '订单不存在');
    header('Location: /admin/orders.php');
    exit;
}

switch ($action) {
    case 'mark_paid':
        if ($order['status'] != 0) {
            flash_set('error', '订单状态不允许确认收款');
            break;
        }
        db_exec('UPDATE orders SET status=1, paid_at=?, updated_at=? WHERE id=?', [$now, $now, $id]);
        flash_set('success', '已确认收款');
        break;

    case 'ship':
        $company = trim($_POST['tracking_company'] ?? '');
        $no = trim($_POST['tracking_no'] ?? '');
        if ($company === '' || $no === '') {
            flash_set('error', '请填写快递公司和快递单号');
            header('Location: ' . $referer);
            exit;
        }
        if ($order['status'] == 1) {
            // 首次发货：待付款→已发货
            db_exec('UPDATE orders SET status=2, tracking_company=?, tracking_no=?, shipped_at=?, updated_at=? WHERE id=?', [
                $company, $no, $now, $now, $id,
            ]);
            flash_set('success', '已发货');

            // 短信通知买家（非阻塞，仅首次发货时通知）
            if (setting('sms_enabled') && is_mobile($order['buyer_phone'])) {
                $tplShip = setting('sms_tpl_ship', '您的订单({order_no})已发货，{company} 单号：{tracking_no}。请留意查收，感谢购买！');
                $smsContent = render_sms_template($tplShip, [
                    'order_no'    => $order['order_no'],
                    'company'     => $company,
                    'tracking_no' => $no,
                    'product'     => $order['product_title'],
                ]);
                @send_sms($order['buyer_phone'], $smsContent);
            }
        } else {
            // 已发货(2) / 已完成(3)：仅更新物流信息，不退状态
            if ($order['status'] == 2 && !$order['shipped_at']) {
                db_exec('UPDATE orders SET tracking_company=?, tracking_no=?, shipped_at=?, updated_at=? WHERE id=?', [
                    $company, $no, $now, $now, $id,
                ]);
            } else {
                db_exec('UPDATE orders SET tracking_company=?, tracking_no=?, updated_at=? WHERE id=?', [
                    $company, $no, $now, $id,
                ]);
            }
            flash_set('success', '物流信息已更新');
        }
        break;

    case 'cancel':
        if (!in_array((int)$order['status'], [0, 1], true)) {
            flash_set('error', '订单状态不允许取消');
            break;
        }
        db_exec('UPDATE orders SET status=-1, updated_at=? WHERE id=?', [$now, $id]);
        flash_set('success', '订单已取消');
        break;

    default:
        flash_set('error', '未知操作');
}

header('Location: ' . $referer);
exit;
