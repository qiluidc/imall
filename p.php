<?php
require_once __DIR__ . '/functions.php';
nocache_headers();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    exit('商品不存在');
}

$product = db_row('SELECT * FROM products WHERE slug = ? AND status = 1', [$slug]);
if (!$product) {
    http_response_code(404);
    exit('商品不存在');
}

$detailImages = json_decode($product['detail_images'] ?? '[]', true) ?: [];
$galleryImages = json_decode($product['gallery_images'] ?? '[]', true) ?: [];
if (empty($galleryImages)) {
    $galleryImages = $product['main_image'] ? [$product['main_image']] : [];
}
$specs = json_decode($product['specs'] ?? '[]', true) ?: [];
$hasSpecs = !empty($specs);

$siteName = setting('site_name', APP_NAME);
$seoTitle = $product['seo_title'] ?: $product['title'] . ' - ' . $siteName;
$seoDesc  = $product['seo_desc'] ?: mb_substr(strip_tags($product['subtitle'] ?? ''), 0, 80);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo e($seoTitle); ?></title>
    <meta name="description" content="<?php echo e($seoDesc); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/shop.css'); ?>">
</head>
<body data-product-id="<?php echo e($product['id']); ?>">
    <div class="shop-wrap product-page">
        <div class="product-gallery" id="productGallery">
            <div class="gallery-track" id="galleryTrack">
                <?php foreach ($galleryImages as $img): ?>
                    <div class="gallery-slide">
                        <img src="<?php echo e(upload_url($img)); ?>" alt="<?php echo e($product['title']); ?>" class="gallery-img" data-full="<?php echo e(upload_url($img)); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($galleryImages) > 1): ?>
                <div class="gallery-dots" id="galleryDots">
                    <?php foreach ($galleryImages as $idx => $img): ?>
                        <span class="dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-i="<?php echo $idx; ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <h1 class="product-title"><?php echo e($product['title']); ?></h1>
            <?php if ($product['subtitle']): ?>
                <p class="product-subtitle"><?php echo e($product['subtitle']); ?></p>
            <?php endif; ?>
            <div class="product-price-row">
                <span class="price" id="priceDisplay">¥<?php echo e(format_money($product['price'])); ?></span>
                <?php if ($product['original_price'] > 0): ?>
                    <span class="original" id="originalDisplay">¥<?php echo e(format_money($product['original_price'])); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($product['stock'] > 0): ?>
                <p class="stock-text">库存：<span id="stockDisplay"><?php echo e($product['stock']); ?></span></p>
            <?php else: ?>
                <p class="stock-text out">暂时缺货</p>
            <?php endif; ?>
        </div>

        <?php if ($hasSpecs): ?>
            <div class="section spec-section">
                <h3>选择规格</h3>
                <div class="spec-list" id="specList">
                    <?php foreach ($specs as $idx => $spec): ?>
                        <button type="button"
                                class="spec-btn <?php echo $idx === 0 ? 'active' : ''; ?>"
                                data-key="<?php echo e($spec['key']); ?>"
                                data-label="<?php echo e($spec['label']); ?>"
                                data-price="<?php echo e($spec['price']); ?>"
                                data-stock="<?php echo e($spec['stock']); ?>">
                            <?php echo e($spec['label']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="section qty-section">
            <h3>数量</h3>
            <div class="qty-control">
                <button type="button" id="qtyMinus">-</button>
                <input type="number" id="quantity" value="1" min="1" max="<?php echo e($product['stock']); ?>">
                <button type="button" id="qtyPlus">+</button>
            </div>
        </div>

        <div class="section form-section">
            <h3>收货信息</h3>
            <div class="form-group">
                <label>收货人</label>
                <input type="text" id="buyerName" placeholder="请输入收货人姓名" maxlength="50">
            </div>
            <div class="form-group">
                <label>手机号码</label>
                <input type="tel" id="buyerPhone" placeholder="请输入手机号" maxlength="11">
            </div>
            <div class="form-group">
                <label>详细地址</label>
                <textarea id="buyerAddress" rows="3" placeholder="省 / 市 / 区 / 街道 / 门牌号"></textarea>
            </div>
            <div class="form-group">
                <label>备注（可选）</label>
                <input type="text" id="buyerRemark" placeholder="如留言、指定快递等" maxlength="200">
            </div>
        </div>

        <?php if (!empty($detailImages)): ?>
            <div class="section detail-section">
                <h3>商品详情</h3>
                <div class="detail-imgs" id="detailImgs">
                    <?php foreach ($detailImages as $img): ?>
                        <img src="<?php echo e(upload_url($img)); ?>" alt="商品详情" class="detail-img" data-full="<?php echo e(upload_url($img)); ?>">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="safe-bottom"></div>

        <div class="product-action-bar">
            <div class="total">
                合计：<span class="total-price" id="totalPrice">¥<?php echo e(format_money($product['price'])); ?></span>
            </div>
            <button type="button" class="submit-btn" id="submitOrder" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                <?php echo $product['stock'] <= 0 ? '暂时缺货' : '立即下单'; ?>
            </button>
        </div>
    </div>

    <div class="btn-float-group">
        <a href="/order.php" class="btn-query-order" title="查询订单">查单</a>
        <a href="/contact.php" class="btn-service" title="联系客服">客服</a>
    </div>

    <div class="img-lightbox" id="imgLightbox"><img alt="预览"></div>

    <script>
        window.PRODUCT_ID = <?php echo json_encode($product['id']); ?>;
        window.HAS_SPECS = <?php echo json_encode($hasSpecs); ?>;
        window.BASE_PRICE = <?php echo json_encode((float)$product['price']); ?>;
        window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
    </script>
    <script src="<?php echo asset('js/shop.js'); ?>"></script>
</body>
</html>
