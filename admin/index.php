<?php
require_once __DIR__ . '/common.php';
require_admin();

$stats = [
    'products' => db_one('SELECT COUNT(*) FROM products') ?: 0,
    'orders_today' => db_one('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND deleted_at IS NULL', [strtotime('today')]) ?: 0,
    'orders_pending' => db_one('SELECT COUNT(*) FROM orders WHERE status = 0 AND deleted_at IS NULL') ?: 0,
    'orders_paid' => db_one('SELECT COUNT(*) FROM orders WHERE status = 1 AND deleted_at IS NULL') ?: 0,
    'orders_shipped' => db_one('SELECT COUNT(*) FROM orders WHERE status = 2 AND deleted_at IS NULL') ?: 0,
    'revenue_today' => db_one('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN (1,2,3) AND created_at >= ? AND deleted_at IS NULL', [strtotime('today')]) ?: 0,
];

$latestOrders = db_all('SELECT o.*, p.slug FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.deleted_at IS NULL ORDER BY o.created_at DESC LIMIT 10');

admin_page_start('概览');
?>
<div class="page-header">
    <h2>概览</h2>
</div>
<?php echo flash_html(); ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-label">商品总数</span>
        <strong class="stat-value"><?php echo e($stats['products']); ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">今日订单</span>
        <strong class="stat-value"><?php echo e($stats['orders_today']); ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">待付款</span>
        <strong class="stat-value"><?php echo e($stats['orders_pending']); ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">已付款待发货</span>
        <strong class="stat-value"><?php echo e($stats['orders_paid']); ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">已发货</span>
        <strong class="stat-value"><?php echo e($stats['orders_shipped']); ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">今日成交额</span>
        <strong class="stat-value">¥<?php echo e(format_money((float)$stats['revenue_today'])); ?></strong>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>最近订单</h3>
        <a href="/admin/orders.php">查看全部</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>订单号</th>
                    <th>商品</th>
                    <th>金额</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latestOrders as $o): ?>
                    <tr>
                        <td><?php echo e($o['order_no']); ?></td>
                        <td><?php echo e($o['product_title']); ?> <?php echo $o['spec_label'] ? '(' . e($o['spec_label']) . ')' : ''; ?></td>
                        <td>¥<?php echo e(format_money($o['total_amount'])); ?></td>
                        <td><span class="badge badge-<?php echo e(order_status_class((int)$o['status'])); ?>"><?php echo e(order_status_label((int)$o['status'])); ?></span></td>
                        <td><?php echo e(date('m-d H:i', $o['created_at'])); ?></td>
                        <td><a href="/admin/order_view.php?id=<?php echo e($o['id']); ?>">详情</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($latestOrders)): ?>
                    <tr><td colspan="6" class="empty-td">暂无订单</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_page_end(); ?>
