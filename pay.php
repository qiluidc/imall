<?php
require_once __DIR__ . '/functions.php';
nocache_headers();

$orderNo = $_GET['order_no'] ?? '';
if (!$orderNo) {
    http_response_code(404);
    exit('订单不存在');
}

$order = db_row('SELECT o.*, p.payment_qr AS product_payment_qr FROM orders o LEFT JOIN products p ON p.id = o.product_id WHERE o.order_no = ? AND o.deleted_at IS NULL', [$orderNo]);
if (!$order) {
    http_response_code(404);
    exit('订单不存在');
}

$qr = ($order['product_payment_qr'] ?? '') ? $order['product_payment_qr'] : setting('payment_qr');
$tip = setting('payment_tip', '保存卡片到相册，微信「扫一扫→相册」识别付款；支付后请等待后台确认收款。');
$cardExtra = setting('payment_card_extra', '');
$siteName = setting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>支付订单 - <?php echo e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/shop.css'); ?>">
</head>
<body>
    <div class="shop-wrap pay-page">
        <div class="pay-card">
            <div class="pay-status">
                <?php if ($order['status'] == 0): ?>
                    <span class="status-dot warning"></span> 等待付款
                <?php elseif ($order['status'] == 1): ?>
                    <span class="status-dot info"></span> 已付款，等待发货
                <?php elseif ($order['status'] == 2): ?>
                    <span class="status-dot primary"></span> 已发货
                <?php elseif ($order['status'] == 3): ?>
                    <span class="status-dot success"></span> 已完成
                <?php else: ?>
                    <span class="status-dot muted"></span> 已取消
                <?php endif; ?>
            </div>

            <div class="pay-amount">
                <p>订单金额</p>
                <h2>¥<?php echo e(format_money($order['total_amount'])); ?></h2>
            </div>

            <div class="order-info-list">
                <div class="info-row">
                    <span>订单编号</span>
                    <strong><?php echo e($order['order_no']); ?></strong>
                </div>
                <div class="info-row">
                    <span>商品</span>
                    <strong><?php echo e($order['product_title']); ?> <?php echo $order['spec_label'] ? '(' . e($order['spec_label']) . ')' : ''; ?> ×<?php echo e($order['quantity']); ?></strong>
                </div>
                <div class="info-row">
                    <span>收货人</span>
                    <strong><?php echo e($order['buyer_name']); ?> <?php echo e($order['buyer_phone']); ?></strong>
                </div>
                <div class="info-row">
                    <span>收货地址</span>
                    <strong><?php echo e($order['buyer_address']); ?></strong>
                </div>
            </div>
        </div>

        <?php if ($order['status'] == 0): ?>
            <div class="qr-card">
                <?php if ($qr): ?>
                    <div class="qr-placeholder" id="qrPlaceholder" style="display:none;">卡片生成失败，请联系管理员</div>
                    <button type="button" class="btn-pay-card" id="genCardBtn">自行扫码支付</button>
                    <div class="card-result" id="cardResult" style="display:none;">
                        <img id="payCardImg" alt="付款卡片，长按可保存到手机">
                    </div>
                <?php else: ?>
                    <div class="qr-placeholder">
                        收款码未配置，请联系管理员
                    </div>
                <?php endif; ?>
                <p class="qr-hint">卡片已包含转账备注手机号，保存后微信扫一扫识别即可付款</p>
            </div>

            <div class="pay-notice">
                <p>支付完成后，可在下方输入手机号查询订单状态。</p>
            </div>
        <?php endif; ?>

        <?php if ($order['status'] == 2 && $order['tracking_no']): ?>
            <div class="logistics-card">
                <h3>物流信息</h3>
                <div class="info-row">
                    <span>快递公司</span>
                    <strong><?php echo e($order['tracking_company']); ?></strong>
                </div>
                <div class="info-row">
                    <span>快递单号</span>
                    <strong><?php echo e($order['tracking_no']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <div class="query-card">
            <h3>查询订单</h3>
            <form action="/order.php" method="get">
                <input type="tel" name="phone" placeholder="输入下单手机号" maxlength="11" value="<?php echo e($order['buyer_phone']); ?>">
                <button type="submit">查询</button>
            </form>
        </div>
    </div>
    <script>
        window.PAY_ORDER_NO = <?php echo json_encode($orderNo); ?>;
    </script>
    <script>
    (function () {
        var btn = document.getElementById('genCardBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var img = document.getElementById('payCardImg');
            img.onerror = function () {
                img.onerror = null;
                document.getElementById('cardResult').style.display = 'none';
                var ph = document.getElementById('qrPlaceholder');
                if (ph) { ph.textContent = '卡片生成失败，请联系管理员'; ph.style.display = 'flex'; }
                btn.style.display = 'inline-block';
            };
            img.src = 'pay_card.php?order_no=' + encodeURIComponent(window.PAY_ORDER_NO);
            document.getElementById('cardResult').style.display = 'block';
            var ph = document.getElementById('qrPlaceholder');
            if (ph) ph.style.display = 'none';
            btn.style.display = 'none';
        });
    })();
    </script>
</body>
</html>
