<?php
require_once __DIR__ . '/common.php';
require_admin();

$total = (int)(db_row('SELECT COUNT(*) AS c FROM products')['c'] ?? 0);
$pg = paginate($total, 20);
$products = db_all('SELECT * FROM products ORDER BY sort DESC, id DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset']);
$_GET['__total__'] = $total;

// 每个商品的订单数（回收站订单不计入，与订单页状态统计口径一致）
$orderCounts = [];
foreach (db_all('SELECT product_id, COUNT(*) AS c FROM orders WHERE deleted_at IS NULL GROUP BY product_id') as $r) {
    $orderCounts[(int)$r['product_id']] = (int)$r['c'];
}

admin_page_start('商品管理');
?>
<div class="page-header">
    <h2>商品管理</h2>
    <a href="/admin/product_edit.php" class="btn-primary">+ 新建商品</a>
</div>
<?php echo flash_html(); ?>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>主图</th>
                    <th>标题</th>
                    <th>售价</th>
                    <th>库存</th>
                    <th>状态</th>
                    <th>排序</th>
                    <th>单页链接</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo e($p['id']); ?></td>
                        <td>
                            <?php if ($p['main_image']): ?>
                                <img src="<?php echo e(upload_url($p['main_image'])); ?>" class="thumb">
                            <?php else: ?>
                                <span class="no-img">无图</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($p['title']); ?></td>
                        <td>¥<?php echo e(format_money($p['price'])); ?></td>
                        <td><?php echo e($p['stock']); ?></td>
                        <td><span class="badge <?php echo $p['status'] == 1 ? 'badge-success' : 'badge-muted'; ?>" data-status="<?php echo (int)$p['status']; ?>"><?php echo $p['status'] == 1 ? '上架' : '下架'; ?></span></td>
                        <td><?php echo e($p['sort']); ?></td>
                        <td><a href="/p.php?slug=<?php echo e($p['slug']); ?>" target="_blank">/p.php?slug=<?php echo e($p['slug']); ?></a></td>
                        <td>
                            <button type="button" class="btn-sm btn-toggle-status" data-id="<?php echo (int)$p['id']; ?>" data-status="<?php echo (int)$p['status']; ?>"><?php echo $p['status'] == 1 ? '下架' : '上架'; ?></button>
                            <a href="/admin/orders.php?product_id=<?php echo (int)$p['id']; ?>" class="btn-sm btn-orders" title="查看该商品的订单">订单 <?php echo (int)($orderCounts[(int)$p['id']] ?? 0); ?></a>
                            <a href="/admin/product_edit.php?id=<?php echo e($p['id']); ?>" class="btn-sm">编辑</a>
                            <button type="button" class="btn-sm btn-duplicate" data-id="<?php echo (int)$p['id']; ?>">复制</button>
                            <a href="/p.php?slug=<?php echo e($p['slug']); ?>" target="_blank" class="btn-sm">预览</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr><td colspan="9" class="empty-td">暂无商品，点击右上角新建</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo render_pagination($pg, '/admin/products.php'); ?>
</div>
<script>
    window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
    document.addEventListener('DOMContentLoaded', function () {
        var token = window.CSRF_TOKEN || '';
        function post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': token },
                body: new URLSearchParams(data).toString(),
            }).then(function (r) { return r.json(); });
        }
        // 上下架快速切换
        document.querySelectorAll('.btn-toggle-status').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.dataset.id;
                var label = this.textContent.trim();
                if (!confirm('确认' + label + '该商品？')) return;
                var old = this;
                old.disabled = true;
                post('/admin/api/product_toggle_status.php', { id: id }).then(function (res) {
                    if (res.code === 0) {
                        alert(res.msg);
                        window.location.reload();
                    } else {
                        alert(res.msg || '操作失败');
                        old.disabled = false;
                    }
                }).catch(function () { alert('网络错误'); old.disabled = false; });
            });
        });
        // 复制商品（slug+数字递增、标题-草稿、下架）
        document.querySelectorAll('.btn-duplicate').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.dataset.id;
                if (!confirm('复制该商品为草稿（下架状态）？')) return;
                var old = this;
                old.disabled = true;
                post('/admin/api/product_duplicate.php', { id: id }).then(function (res) {
                    if (res.code === 0) {
                        alert(res.msg);
                        window.location.reload();
                    } else {
                        alert(res.msg || '复制失败');
                        old.disabled = false;
                    }
                }).catch(function () { alert('网络错误'); old.disabled = false; });
            });
        });
    });
</script>
<?php admin_page_end(); ?>
