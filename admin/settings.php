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
    'sms_enabled'         => setting('sms_enabled', 0),
    'sms_api_key'         => setting('sms_api_key'),
    'sms_signature'       => setting('sms_signature'),
    'admin_notify_mobiles'=> setting('admin_notify_mobiles'),
    'sms_tpl_order'       => setting('sms_tpl_order', '新订单待处理：{product} x{quantity}，合计¥{amount}。收货人：{buyer}({phone})，地址：{address}。订单号：{order_no}'),
    'sms_tpl_ship'        => setting('sms_tpl_ship', '您的订单({order_no})已发货，{company} 单号：{tracking_no}。请留意查收，感谢购买！'),
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
        <div class="form-card-head">
            <h3>短信通知（螺丝帽）</h3>
            <label class="toggle" title="开启 / 关闭短信通知">
                <input type="checkbox" name="sms_enabled" value="1" <?php echo $settings['sms_enabled'] ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="form-group">
            <label>螺丝帽 API Key</label>
            <input type="text" name="sms_api_key" value="<?php echo e($settings['sms_api_key']); ?>" placeholder="key-xxxxxxxxxxxx">
            <small>登录螺丝帽控制台 →【短信服务】→【触发发送】获取 API Key</small>
        </div>
        <div class="form-group">
            <label>短信签名（不含括号）</label>
            <input type="text" name="sms_signature" value="<?php echo e($settings['sms_signature']); ?>" placeholder="例如：云谷优选">
            <small>系统会自动包裹为【签名】，该签名需在螺丝帽后台已通过审核</small>
        </div>
        <div class="form-group">
            <label>下单通知模板（发给管理员）</label>
            <textarea name="sms_tpl_order" rows="3"><?php echo e($settings['sms_tpl_order']); ?></textarea>
            <small>可用变量：{product} {quantity} {amount} {buyer} {phone} {address} {order_no}</small>
        </div>
        <div class="form-group">
            <label>发货通知模板（发给买家）</label>
            <textarea name="sms_tpl_ship" rows="3"><?php echo e($settings['sms_tpl_ship']); ?></textarea>
            <small>可用变量：{order_no} {company} {tracking_no} {product}</small>
        </div>
        <div class="form-two-col">
            <div class="form-group">
                <label>管理员接收手机号（多个用逗号分隔）</label>
                <input type="text" name="admin_notify_mobiles" value="<?php echo e($settings['admin_notify_mobiles']); ?>" placeholder="13800138000,13900139000">
                <small>用户下单后，向这些号码发送新订单通知</small>
            </div>
            <div class="form-group">
                <label>发送测试</label>
                <div class="inline-with-btn">
                    <input type="text" id="smsTestMobile" placeholder="测试手机号" autocomplete="off">
                    <button type="button" class="btn-primary-sm" id="smsTestBtn">发送测试</button>
                </div>
                <small id="smsTestResult"></small>
            </div>
        </div>
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
