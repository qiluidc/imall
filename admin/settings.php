<?php
require_once __DIR__ . '/common.php';
require_admin();

$settings = [
    'site_name'      => setting('site_name', APP_NAME),
    'site_notice'    => setting('site_notice'),
    'payment_qr'     => setting('payment_qr'),
    'payment_tip'    => setting('payment_tip', '请长按二维码识别，支付订单金额。支付完成后请联系客服或等待后台确认。'),
    'payment_card_extra' => setting('payment_card_extra'),
    'contact_phone'  => setting('contact_phone'),
    'contact_wechat' => setting('contact_wechat'),
    'contact_wechat_qr' => setting('contact_wechat_qr'),
];

admin_page_start('系统设置');
?>
<div class="page-header">
    <h2>系统设置</h2>
</div>
<?php echo flash_html(); ?>

<form class="edit-form" id="settingsForm">
    <?php echo csrf_field(); ?>

    <div class="form-card">
        <h3>站点信息</h3>
        <div class="form-group">
            <label>站点名称</label>
            <input type="text" name="site_name" value="<?php echo e($settings['site_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>站点公告（可选，显示在首页顶部）</label>
            <textarea name="site_notice" rows="2"><?php echo e($settings['site_notice']); ?></textarea>
        </div>
    </div>

    <div class="form-card">
        <h3>收款设置</h3>
        <div class="form-group">
            <label>个人收款码</label>
            <div class="upload-wrap">
                <input type="file" id="paymentQrInput" accept="image/*">
                <input type="hidden" name="payment_qr" value="<?php echo e($settings['payment_qr']); ?>">
                <div class="preview" id="paymentQrPreview">
                    <?php if ($settings['payment_qr']): ?>
                        <img src="<?php echo e(upload_url($settings['payment_qr'])); ?>">
                    <?php endif; ?>
                </div>
            </div>
            <small>下单后支付页会展示此二维码，并提示用户支付对应金额。</small>
        </div>
        <div class="form-group">
            <label>支付提示文案</label>
            <textarea name="payment_tip" rows="3"><?php echo e($settings['payment_tip']); ?></textarea>
        </div>
        <div class="form-group">
            <label>卡片其他信息（可选，展示在付款卡片底部）</label>
            <textarea name="payment_card_extra" rows="3"><?php echo e($settings['payment_card_extra']); ?></textarea>
            <small>用户点击「自行扫码支付」生成的卡片上会展示此信息，如店铺活动、售后说明等。</small>
        </div>
    </div>

    <div class="form-card">
        <h3>联系方式（可选）</h3>
        <div class="form-row">
            <div class="form-group">
                <label>客服电话</label>
                <input type="text" name="contact_phone" value="<?php echo e($settings['contact_phone']); ?>">
            </div>
            <div class="form-group">
                <label>客服微信</label>
                <input type="text" name="contact_wechat" value="<?php echo e($settings['contact_wechat']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>客服微信二维码</label>
            <div class="upload-wrap">
                <input type="file" id="contactWechatQrInput" accept="image/*">
                <input type="hidden" name="contact_wechat_qr" value="<?php echo e($settings['contact_wechat_qr']); ?>">
                <div class="preview" id="contactWechatQrPreview">
                    <?php if ($settings['contact_wechat_qr']): ?>
                        <div class="preview-item"><img src="<?php echo e(upload_url($settings['contact_wechat_qr'])); ?>"><button type="button" class="remove-btn" data-clear-single="1">×</button></div>
                    <?php endif; ?>
                </div>
            </div>
            <small>客服页会展示此二维码，买家长按即可识别添加微信。</small>
        </div>
    </div>

    <div class="form-card">
        <h3>通知设置</h3>
        <p class="hint">短信通知（螺丝帽）与邮件通知（发件邮箱）已移至独立的「通知设置」菜单，请到左侧「通知设置」中配置。</p>
    </div>

    <div class="form-card">
        <h3>管理员密码</h3>
        <p class="hint">如需修改后台登录密码，请填写新密码并保存。</p>
        <div class="form-row">
            <div class="form-group">
                <label>新密码</label>
                <input type="password" name="admin_password" placeholder="不修改请留空" minlength="6" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" name="admin_password_confirm" placeholder="不修改请留空" autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary" id="saveSettingsBtn">保存设置</button>
    </div>
</form>

<script>
    window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
</script>
<?php admin_page_end(); ?>
