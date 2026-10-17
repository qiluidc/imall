<?php
require_once __DIR__ . '/functions.php';
nocache_headers();

$siteName       = setting('site_name', APP_NAME);
$contactPhone   = trim((string) setting('contact_phone'));
$contactWechat  = trim((string) setting('contact_wechat'));
$contactWechatQr = trim((string) setting('contact_wechat_qr'));
$hasAny         = $contactPhone !== '' || $contactWechat !== '' || $contactWechatQr !== '';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>联系客服 - <?php echo e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/shop.css'); ?>">
</head>
<body>
    <div class="shop-wrap contact-page">
        <h1>联系客服</h1>
        <p class="contact-sub">如有任何问题，欢迎通过以下方式联系我们</p>

        <?php if (!$hasAny): ?>
            <div class="contact-empty">
                <p>客服联系方式暂未公布</p>
            </div>
        <?php else: ?>
            <?php if ($contactPhone !== ''): ?>
                <div class="contact-card">
                    <h3>客服电话</h3>
                    <div class="contact-row">
                        <span class="label">联系电话</span>
                        <span class="value"><a href="tel:<?php echo e(preg_replace('/\s+/', '', $contactPhone)); ?>"><?php echo e($contactPhone); ?></a></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($contactWechat !== ''): ?>
                <div class="contact-card">
                    <h3>客服微信</h3>
                    <div class="contact-row">
                        <span class="label">微信号</span>
                        <span class="value"><?php echo e($contactWechat); ?></span>
                        <button type="button" class="btn-copy" id="copyWechatBtn">复制</button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($contactWechatQr !== ''): ?>
                <div class="contact-card">
                    <h3>微信二维码</h3>
                    <div class="contact-qr-wrap">
                        <img class="qr-img" src="<?php echo e(upload_url($contactWechatQr)); ?>" alt="客服微信二维码">
                        <p class="qr-tip">长按识别二维码，即可添加客服微信</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <a class="contact-back" href="javascript:history.back();">返回</a>
    </div>

    <script>
    (function () {
        var btn = document.getElementById('copyWechatBtn');
        if (!btn) return;
        var text = <?php echo json_encode($contactWechat); ?>;
        btn.addEventListener('click', function () {
            function done() {
                btn.textContent = '已复制';
                btn.classList.add('done');
                setTimeout(function () {
                    btn.textContent = '复制';
                    btn.classList.remove('done');
                }, 2000);
            }
            function fallback() {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try { document.execCommand('copy'); done(); }
                catch (e) { alert('复制失败，请长按微信号手动复制'); }
                document.body.removeChild(ta);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(fallback);
            } else {
                fallback();
            }
        });
    })();
    </script>
</body>
</html>
