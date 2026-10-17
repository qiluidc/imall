<?php
require_once __DIR__ . '/common.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$order = db_row('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) {
    flash_set('error', '订单不存在');
    header('Location: /admin/orders.php');
    exit;
}

$product = db_row('SELECT slug FROM products WHERE id = ?', [$order['product_id']]);

admin_page_start('订单详情');
?>
<div class="page-header">
    <h2>订单详情</h2>
    <a href="/admin/orders.php" class="btn-link">返回列表</a>
</div>
<?php echo flash_html(); ?>

<div class="two-col">
    <div class="panel">
        <div class="panel-header"><h3>订单信息</h3></div>
        <div class="info-list">
            <div class="info-row"><span>订单编号</span><strong><?php echo e($order['order_no']); ?></strong></div>
            <div class="info-row"><span>订单状态</span><strong><span class="badge badge-<?php echo e(order_status_class((int)$order['status'])); ?>"><?php echo e(order_status_label((int)$order['status'])); ?></span></strong></div>
            <div class="info-row"><span>下单时间</span><strong><?php echo e(date('Y-m-d H:i:s', $order['created_at'])); ?></strong></div>
            <div class="info-row"><span>付款时间</span><strong><?php echo $order['paid_at'] ? e(date('Y-m-d H:i:s', $order['paid_at'])) : '-'; ?></strong></div>
            <div class="info-row"><span>发货时间</span><strong><?php echo $order['shipped_at'] ? e(date('Y-m-d H:i:s', $order['shipped_at'])) : '-'; ?></strong></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>商品信息</h3></div>
        <div class="info-list">
            <div class="info-row"><span>商品</span><strong><?php echo e($order['product_title']); ?></strong></div>
            <div class="info-row"><span>规格</span><strong><?php echo e($order['spec_label']); ?></strong></div>
            <div class="info-row"><span>单价</span><strong>¥<?php echo e(format_money($order['price'])); ?></strong></div>
            <div class="info-row"><span>数量</span><strong><?php echo e($order['quantity']); ?></strong></div>
            <div class="info-row"><span>合计</span><strong class="text-red">¥<?php echo e(format_money($order['total_amount'])); ?></strong></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>收货信息</h3></div>
        <div class="info-list">
            <div class="info-row"><span>收货人</span><strong><?php echo e($order['buyer_name']); ?></strong></div>
            <div class="info-row"><span>手机号</span><strong><?php echo e($order['buyer_phone']); ?></strong></div>
            <div class="info-row"><span>地址</span><strong><?php echo e($order['buyer_address']); ?></strong></div>
            <div class="info-row"><span>备注</span><strong><?php echo e($order['buyer_remark'] ?: '-'); ?></strong></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>物流信息</h3></div>
        <?php if (in_array((int)$order['status'], [1, 2, 3], true)): ?>
        <form method="post" action="/admin/api/order_action.php" class="ship-form" id="shipForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
            <input type="hidden" name="action" value="ship">
            <div class="form-group">
                <label>快递公司 <span class="required">*</span></label>
                <select id="shipCompany" class="ship-company-select">
                    <?php
                    $companies = ['顺丰速运', '圆通速递', '中通快递', '申通快递', '韵达速递', '京东物流', '邮政EMS', '德邦快递', '极兔速递', '百世快递'];
                    $savedCompany = $order['tracking_company'] ?? '';
                    foreach ($companies as $c) {
                        $sel = ($savedCompany !== '' && $savedCompany === $c) ? ' selected' : '';
                        echo '<option value="' . e($c) . '"' . $sel . '>' . e($c) . '</option>';
                    }
                    $otherSel = ($savedCompany !== '' && !in_array($savedCompany, $companies, true)) ? ' selected' : '';
                    echo '<option value="__other__"' . $otherSel . '>其他（手动输入）</option>';
                    ?>
                </select>
                <input type="text" id="shipCompanyOther" class="ship-company-other" placeholder="请输入快递公司名称" value="<?php echo $otherSel ? e($savedCompany) : ''; ?>" style="display:none;margin-top:8px;">
                <input type="hidden" name="tracking_company" id="shipCompanyVal" value="<?php echo e($savedCompany); ?>">
            </div>
            <div class="form-group">
                <label>快递单号 <span class="required">*</span></label>
                <input type="text" name="tracking_no" placeholder="请输入快递单号" value="<?php echo e($order['tracking_no']); ?>" required>
            </div>
            <button type="submit" class="btn-primary"><?php echo $order['status'] == 1 ? '确认发货' : '更新物流'; ?></button>
        </form>
        <script>
        (function () {
            var sel = document.getElementById('shipCompany');
            var other = document.getElementById('shipCompanyOther');
            var val = document.getElementById('shipCompanyVal');
            function sync() {
                if (sel.value === '__other__') {
                    other.style.display = 'block';
                    val.value = other.value;
                } else {
                    other.style.display = 'none';
                    val.value = sel.value;
                }
            }
            sel.addEventListener('change', sync);
            other.addEventListener('input', function () { val.value = other.value; });
            sync();
        })();
        </script>
        <?php else: ?>
        <div class="info-list">
            <div class="info-row"><span>快递公司</span><strong><?php echo e($order['tracking_company'] ?: '-'); ?></strong></div>
            <div class="info-row"><span>快递单号</span><strong><?php echo e($order['tracking_no'] ?: '-'); ?></strong></div>
        </div>
        <?php endif; ?>
    </div>
</div>

    <div class="panel action-panel">
        <div class="panel-header"><h3>操作</h3></div>
        <div class="actions">
            <?php if ($order['status'] == 0): ?>
                <form method="post" action="/admin/api/order_action.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="action" value="mark_paid">
                    <button type="submit" class="btn-primary" onclick="return confirm('确认已收到该订单付款？')">确认收款</button>
                </form>
                <form method="post" action="/admin/api/order_action.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn-sm btn-danger" onclick="return confirm('确认取消该订单？')">取消订单</button>
                </form>
            <?php endif; ?>

            <?php if (empty($order['deleted_at'])): ?>
                <form method="post" action="/admin/api/order_action.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="ids[]" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="action" value="trash">
                    <button type="submit" class="btn-sm btn-danger" onclick="return confirm('移入回收站？')">移入回收站</button>
                </form>
            <?php else: ?>
                <form method="post" action="/admin/api/order_action.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="ids[]" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="action" value="restore">
                    <button type="submit" class="btn-sm" onclick="return confirm('恢复此订单？')">恢复订单</button>
                </form>
                <form method="post" action="/admin/api/order_action.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="ids[]" value="<?php echo e($order['id']); ?>">
                    <input type="hidden" name="action" value="destroy">
                    <button type="submit" class="btn-sm btn-danger" onclick="return confirm('彻底删除将无法恢复，确认？')">彻底删除</button>
                </form>
            <?php endif; ?>

            <?php if ($product && $product['slug']): ?>
                <a href="/p.php?slug=<?php echo e($product['slug']); ?>" target="_blank" class="btn-sm">查看商品页</a>
            <?php endif; ?>
            <a href="/pay.php?order_no=<?php echo e($order['order_no']); ?>" target="_blank" class="btn-sm">查看支付页</a>
        </div>
    </div>
<?php admin_page_end(); ?>
