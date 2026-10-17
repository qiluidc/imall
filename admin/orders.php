<?php
require_once __DIR__ . '/common.php';
require_admin();

$view = ($_GET['view'] ?? 'active') === 'trash' ? 'trash' : 'active';
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$keyword = trim($_GET['keyword'] ?? '');

// 按商品维度查看订单（商品管理 → 订单按钮）；商品不存在则忽略该参数，退回全部订单
$productId = (int)($_GET['product_id'] ?? 0);
$product   = $productId ? db_row('SELECT id, title FROM products WHERE id = ?', [$productId]) : null;
if ($productId && !$product) {
    $productId = 0;
}
$productFilter = $productId ? ' AND product_id = ?' : '';
$productArgs   = $productId ? [$productId] : [];

$where = '1=1';
$params = [];
// 回收站维度独立于业务状态枚举，不污染 status
$where .= $view === 'trash' ? ' AND deleted_at IS NOT NULL' : ' AND deleted_at IS NULL';
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

$total = (int)(db_row('SELECT COUNT(*) AS c FROM orders WHERE ' . $where, $params)['c'] ?? 0);
$pg = paginate($total, 20);
$orders = db_all('SELECT * FROM orders WHERE ' . $where . ' ORDER BY ' . ($view === 'trash' ? 'deleted_at' : 'created_at') . ' DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], $params);
$_GET['__total__'] = $total;

// 各状态订单数（仅正常订单，回收站不计入）；商品视图下只统计该商品
$counts = [];
foreach (db_all('SELECT status, COUNT(*) AS c FROM orders WHERE deleted_at IS NULL' . $productFilter . ' GROUP BY status', $productArgs) as $r) {
    $counts[(int)$r['status']] = (int)$r['c'];
}
$totalCount = array_sum($counts);
$trashCount = (int)(db_row('SELECT COUNT(*) AS c FROM orders WHERE deleted_at IS NOT NULL' . $productFilter, $productArgs)['c'] ?? 0);

$exportParams = [];
if ($view === 'trash') $exportParams['view'] = 'trash';
if ($status !== null) $exportParams['status'] = $status;
if ($keyword !== '') $exportParams['keyword'] = $keyword;
if ($productId) $exportParams['product_id'] = $productId;
$exportQuery = http_build_query($exportParams);

admin_page_start('订单管理');
?>
<div class="page-header">
    <h2>订单管理<?php echo $view === 'trash' ? ' · 回收站' : ''; ?><?php echo $product ? ' · ' . e($product['title']) : ''; ?></h2>
    <?php if ($product): ?>
        <a href="/admin/orders.php" class="btn-sm">查看全部订单</a>
    <?php endif; ?>
</div>
<?php echo flash_html(); ?>

<div class="panel">
    <?php
    $statusTabs = [
        ['st' => null, 'label' => '全部'],
        ['st' => 0,   'label' => '待付款'],
        ['st' => 1,   'label' => '已付款'],
        ['st' => 2,   'label' => '已发货'],
        ['st' => 3,   'label' => '已完成'],
        ['st' => -1,  'label' => '已取消'],
    ];
    $chipBase = [];
    if ($view === 'trash') $chipBase['view'] = 'trash';
    if ($keyword !== '') $chipBase['keyword'] = $keyword;
    if ($productId) $chipBase['product_id'] = $productId;
    ?>
    <div class="status-chips">
        <?php foreach ($statusTabs as $tab):
            $q = $chipBase;
            if ($tab['st'] !== null) $q['status'] = $tab['st'];
            $url = '/admin/orders.php' . ($q ? '?' . http_build_query($q) : '');
            $active = $status === $tab['st'];
            $cnt = $tab['st'] === null ? $totalCount : ($counts[$tab['st']] ?? 0);
        ?>
            <a href="<?php echo e($url); ?>" class="status-chip<?php echo $active ? ' is-active' : ''; ?>">
                <?php echo e($tab['label']); ?>
                <span class="count"><?php echo (int)$cnt; ?></span>
            </a>
        <?php endforeach; ?>
        <?php
        $trashChipQ = ['view' => 'trash'];
        if ($productId) $trashChipQ['product_id'] = $productId;
        if ($keyword !== '') $trashChipQ['keyword'] = $keyword;
        ?>
        <a href="/admin/orders.php?<?php echo e(http_build_query($trashChipQ)); ?>" class="status-chip status-chip-trash<?php echo $view === 'trash' ? ' is-active' : ''; ?>">
            回收站
            <span class="count"><?php echo $trashCount; ?></span>
        </a>
    </div>

    <?php
    $resetQ = [];
    if ($view === 'trash') $resetQ['view'] = 'trash';
    if ($productId) $resetQ['product_id'] = $productId;
    $resetUrl = '/admin/orders.php' . ($resetQ ? '?' . http_build_query($resetQ) : '');
    ?>
    <form class="filter-bar" method="get" action="/admin/orders.php">
        <?php if ($view === 'trash'): ?><input type="hidden" name="view" value="trash"><?php endif; ?>
        <?php if ($productId): ?><input type="hidden" name="product_id" value="<?php echo (int)$productId; ?>"><?php endif; ?>
        <input type="hidden" name="status" value="<?php echo $status !== null ? (int)$status : ''; ?>">
        <input type="text" name="keyword" placeholder="订单号 / 手机 / 姓名 / 商品" value="<?php echo e($keyword); ?>">
        <button type="submit" class="btn-sm btn-filter">筛选</button>
        <a href="<?php echo e($resetUrl); ?>" class="btn-sm btn-reset">重置</a>
        <?php if ($view === 'active'): ?>
        <a href="/admin/api/orders_export.php<?php echo $exportQuery ? '?' . $exportQuery : ''; ?>" class="btn-sm btn-export">导出表格</a>
        <?php endif; ?>
    </form>

    <form method="post" action="/admin/api/order_action.php" id="batchForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" id="batchAction" value="">
        <div class="batch-bar">
            <span class="batch-count">已选 <b id="batchCount">0</b> 单</span>
            <?php if ($view === 'active'): ?>
                <button type="button" class="btn-sm btn-danger" data-action="trash" data-batch="1" data-confirm="移入选中订单到回收站？">批量删除</button>
            <?php else: ?>
                <button type="button" class="btn-sm" data-action="restore" data-batch="1" data-confirm="恢复选中的订单？">批量恢复</button>
                <button type="button" class="btn-sm btn-danger" data-action="destroy" data-batch="1" data-confirm="彻底删除将无法恢复，确认？">批量彻底删除</button>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" id="selectAllTopHead"></th>
                        <th>订单号</th>
                        <th>商品</th>
                        <th>买家</th>
                        <th>金额</th>
                        <th>状态</th>
                        <th><?php echo $view === 'trash' ? '删除时间' : '下单时间'; ?></th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="col-check"><input type="checkbox" name="ids[]" value="<?php echo (int)$o['id']; ?>" class="row-check"></td>
                            <td><?php echo e($o['order_no']); ?></td>
                            <td>
                                <?php echo e($o['product_title']); ?>
                                <?php echo $o['spec_label'] ? '<br><small>' . e($o['spec_label']) . ' ×' . e($o['quantity']) . '</small>' : '<br><small>×' . e($o['quantity']) . '</small>'; ?>
                            </td>
                            <td><?php echo e($o['buyer_name']); ?><br><small><?php echo e($o['buyer_phone']); ?></small></td>
                            <td>¥<?php echo e(format_money($o['total_amount'])); ?></td>
                            <td><span class="badge badge-<?php echo e(order_status_class((int)$o['status'])); ?>"><?php echo e(order_status_label((int)$o['status'])); ?></span></td>
                            <td><?php echo $view === 'trash'
                                ? e($o['deleted_at'] ? date('Y-m-d H:i', strtotime($o['deleted_at'])) : '-')
                                : e(date('Y-m-d H:i', $o['created_at'])); ?></td>
                            <td class="col-ops">
                                <a href="/admin/order_view.php?id=<?php echo (int)$o['id']; ?>" class="btn-sm">详情</a>
                                <?php if ($view === 'active'): ?>
                                    <button type="button" class="btn-sm btn-danger" data-action="trash" data-row="<?php echo (int)$o['id']; ?>" data-confirm="移入回收站？">删除</button>
                                <?php else: ?>
                                    <button type="button" class="btn-sm" data-action="restore" data-row="<?php echo (int)$o['id']; ?>" data-confirm="恢复此订单？">恢复</button>
                                    <button type="button" class="btn-sm btn-danger" data-action="destroy" data-row="<?php echo (int)$o['id']; ?>" data-confirm="彻底删除将无法恢复，确认？">彻底删除</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" class="empty-td"><?php echo $view === 'trash' ? '回收站为空' : '无匹配订单'; ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php echo render_pagination($pg, '/admin/orders.php', ['view', 'status', 'keyword', 'product_id']); ?>
    <?php if ($view === 'trash'): ?>
        <p class="hint hint-below-table">回收站订单不计入状态统计；彻底删除将永久移除，不可恢复。</p>
    <?php endif; ?>
</div>
<script>
    (function () {
        var form = document.getElementById('batchForm');
        if (!form) return;
        var head = document.getElementById('selectAllTopHead');
        function rowChecks() { return Array.prototype.slice.call(document.querySelectorAll('.row-check')); }
        function syncCount() {
            var n = rowChecks().filter(function (c) { return c.checked; }).length;
            var bc = document.getElementById('batchCount'); if (bc) bc.textContent = n;
            var st = rowChecks().length && n === rowChecks().length;
            if (head) head.checked = st;
        }
        if (head) head.addEventListener('change', function () {
            rowChecks().forEach(function (c) { c.checked = head.checked; });
            syncCount();
        });
        rowChecks().forEach(function (c) { c.addEventListener('change', syncCount); });
        syncCount();

        // 统一操作入口：批量靠已勾选行，单条先勾选该行，ids 全走 checkbox
        document.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (this.dataset.confirm && !confirm(this.dataset.confirm)) return;
                var action = this.dataset.action;
                var rowId = this.dataset.row;
                if (rowId) {
                    rowChecks().forEach(function (c) { c.checked = false; });
                    var target = document.querySelector('.row-check[value="' + rowId + '"]');
                    if (target) target.checked = true;
                } else {
                    var sel = rowChecks().filter(function (c) { return c.checked; });
                    if (!sel.length) { alert('请先勾选订单'); return; }
                }
                document.getElementById('batchAction').value = action;
                form.submit();
            });
        });
    })();
</script>
<?php admin_page_end(); ?>
