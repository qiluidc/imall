<?php
require_once __DIR__ . '/common.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = [
    'id' => 0,
    'slug' => '',
    'title' => '',
    'subtitle' => '',
    'main_image' => '',
    'gallery_images' => '[]',
    'detail_images' => '[]',
    'specs' => '[]',
    'price' => '0.00',
    'original_price' => '0.00',
    'stock' => 0,
    'status' => 1,
    'sort' => 0,
    'payment_qr' => '',
    'seo_title' => '',
    'seo_desc' => '',
];

if ($id > 0) {
    $row = db_row('SELECT * FROM products WHERE id = ?', [$id]);
    if ($row) $product = $row;
}

$detailImages = json_decode($product['detail_images'] ?? '[]', true) ?: [];
$galleryImages = json_decode($product['gallery_images'] ?? '[]', true) ?: [];
$specs = json_decode($product['specs'] ?? '[]', true) ?: [];

admin_page_start($id > 0 ? '编辑商品' : '新建商品');
?>
<div class="page-header">
    <h2><?php echo $id > 0 ? '编辑商品' : '新建商品'; ?></h2>
</div>
<?php echo flash_html(); ?>

<form class="edit-form" id="productForm">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo e($product['id']); ?>">

    <div class="form-actions form-actions-top">
        <button type="submit" class="btn-primary" id="saveBtn">保存商品</button>
        <a href="/admin/products.php" class="btn-link">返回列表</a>
    </div>

    <div class="form-card">
        <h3>基础信息</h3>
        <div class="form-row">
            <div class="form-group">
                <label>商品标题 <span class="required">*</span></label>
                <input type="text" name="title" value="<?php echo e($product['title']); ?>" required maxlength="255">
            </div>
            <div class="form-group">
                <label>URL 标识(slug) <span class="required">*</span></label>
                <input type="text" name="slug" value="<?php echo e($product['slug']); ?>" required maxlength="64" placeholder="如：iphone15-case">
                <small>每个商品独立单页地址：/p.php?slug=<b id="slugPreview"></b></small>
            </div>
        </div>
        <div class="form-group">
            <label>副标题</label>
            <input type="text" name="subtitle" value="<?php echo e($product['subtitle']); ?>" maxlength="255">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>售价 <span class="required">*</span></label>
                <input type="number" name="price" value="<?php echo e($product['price']); ?>" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>划线价</label>
                <input type="number" name="original_price" value="<?php echo e($product['original_price']); ?>" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>库存</label>
                <input type="number" name="stock" value="<?php echo e($product['stock']); ?>" min="0">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>状态</label>
                <select name="status">
                    <option value="1" <?php echo $product['status'] == 1 ? 'selected' : ''; ?>>上架</option>
                    <option value="0" <?php echo $product['status'] == 0 ? 'selected' : ''; ?>>下架</option>
                </select>
            </div>
            <div class="form-group">
                <label>排序(越大越靠前)</label>
                <input type="number" name="sort" value="<?php echo e($product['sort']); ?>">
            </div>
        </div>
    </div>

    <div class="form-card">
        <h3>图片</h3>
        <div class="form-group">
            <label>主图</label>
            <div class="upload-wrap">
                <input type="file" id="mainImageInput" accept="image/*">
                <input type="hidden" name="main_image" value="<?php echo e($product['main_image']); ?>">
                <div class="preview" id="mainImagePreview">
                    <?php if ($product['main_image']): ?>
                        <img src="<?php echo e(upload_url($product['main_image'])); ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>详情图（可多张）</label>
            <div class="upload-wrap multi">
                <input type="file" id="detailImagesInput" accept="image/*" multiple>
                <div class="preview-list" id="detailImagesPreview">
                    <?php foreach ($detailImages as $img): ?>
                        <div class="preview-item">
                            <img src="<?php echo e(upload_url($img)); ?>">
                            <button type="button" class="remove-btn" data-path="<?php echo e($img); ?>">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="detail_images" id="detailImagesInputHidden" value='<?php echo e($product['detail_images'] ?: '[]'); ?>'>
        </div>
        <div class="form-group">
            <label>图集（顶部轮播，可多张）</label>
            <div class="upload-wrap multi">
                <input type="file" id="galleryImagesInput" accept="image/*" multiple>
                <div class="preview-list" id="galleryImagesPreview">
                    <?php foreach ($galleryImages as $img): ?>
                        <div class="preview-item">
                            <img src="<?php echo e(upload_url($img)); ?>">
                            <button type="button" class="remove-btn" data-path="<?php echo e($img); ?>">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="gallery_images" id="galleryImagesInputHidden" value='<?php echo e($product['gallery_images'] ?: '[]'); ?>'>
        </div>
    </div>

    <div class="form-card">
        <h3>收款码（可选）</h3>
        <p class="hint">为该商品单独设置收款码；留空则使用后台「系统设置」中的通用收款码兜底。</p>
        <div class="form-group">
            <label>商品收款码</label>
            <div class="upload-wrap">
                <input type="file" id="paymentQrInput" accept="image/*">
                <input type="hidden" name="payment_qr" value="<?php echo e($product['payment_qr']); ?>">
                <div class="preview" id="paymentQrPreview">
                    <?php if ($product['payment_qr']): ?>
                        <div class="preview-item">
                            <img src="<?php echo e(upload_url($product['payment_qr'])); ?>">
                            <button type="button" class="remove-btn" id="clearPaymentQr">×</button>
                        </div>
                    <?php endif; ?>
                </div>
                <small>支持微信/支付宝收款码截图。如需恢复使用通用收款码，点击上方 × 清除即可。</small>
            </div>
        </div>
    </div>

    <div class="form-card">
        <h3>规格</h3>
        <p class="hint">不添加规格时，商品按默认规格售卖；添加后用户必须选择其一。</p>
        <div id="specsArea">
            <?php foreach ($specs as $idx => $s): ?>
                <div class="spec-row">
                    <input type="text" placeholder="规格名称" class="spec-label" value="<?php echo e($s['label']); ?>">
                    <input type="text" placeholder="标识(key)" class="spec-key" value="<?php echo e($s['key']); ?>">
                    <input type="number" placeholder="价格" class="spec-price" value="<?php echo e($s['price']); ?>" step="0.01">
                    <input type="number" placeholder="库存" class="spec-stock" value="<?php echo e($s['stock']); ?>">
                    <button type="button" class="btn-sm btn-danger remove-spec">删除</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-sm" id="addSpec">+ 添加规格</button>
        <input type="hidden" name="specs" id="specsHidden" value='<?php echo e($product['specs'] ?: '[]'); ?>'>
    </div>

    <div class="form-card">
        <h3>SEO</h3>
        <div class="form-group">
            <label>页面标题</label>
            <input type="text" name="seo_title" value="<?php echo e($product['seo_title']); ?>" maxlength="255">
        </div>
        <div class="form-group">
            <label>页面描述</label>
            <textarea name="seo_desc" rows="2" maxlength="500"><?php echo e($product['seo_desc']); ?></textarea>
        </div>
    </div>

</form>

<script>
    window.PRODUCT_ID = <?php echo json_encode($id); ?>;
    window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
</script>
<?php admin_page_end(); ?>
