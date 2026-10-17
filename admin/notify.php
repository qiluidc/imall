<?php
require_once __DIR__ . '/common.php';
require_admin();

$settings = [
    'sms_enabled'          => setting('sms_enabled', 0),
    'sms_api_key'          => setting('sms_api_key'),
    'sms_signature'        => setting('sms_signature'),
    'admin_notify_mobiles' => setting('admin_notify_mobiles'),
    'sms_tpl_order'        => setting('sms_tpl_order', '新订单待处理：{product} x{quantity}，合计¥{amount}。收货人：{buyer}({phone})，地址：{address}。订单号：{order_no}'),
    'sms_tpl_ship'         => setting('sms_tpl_ship', '您的订单({order_no})已发货，{company} 单号：{tracking_no}。请留意查收，感谢购买！'),
    'mail_enabled'         => setting('mail_enabled', 0),
    'mail_smtp_host'       => setting('mail_smtp_host'),
    'mail_smtp_port'       => setting('mail_smtp_port', 465),
    'mail_smtp_secure'     => setting('mail_smtp_secure', 'ssl'),
    'mail_smtp_user'       => setting('mail_smtp_user'),
    'mail_smtp_pass'       => setting('mail_smtp_pass'),
    'mail_from_address'    => setting('mail_from_address'),
    'mail_from_name'       => setting('mail_from_name'),
    'mail_admin_emails'    => setting('mail_admin_emails'),
];

admin_page_start('通知设置');
?>
<div class="page-header">
    <h2>通知设置</h2>
</div>
<?php echo flash_html(); ?>

<form class="edit-form" id="notifyForm">
    <?php echo csrf_field(); ?>

    <div class="form-card">
        <div class="form-card-head">
            <h3>邮件通知（SMTP）</h3>
            <label class="toggle" title="开启 / 关闭邮件通知">
                <input type="checkbox" name="mail_enabled" value="1" <?php echo $settings['mail_enabled'] ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <p class="hint">配置发件邮箱后，可用下方「发送测试」验证通道是否可用。</p>
        <div class="form-row">
            <div class="form-group">
                <label>SMTP 服务器</label>
                <input type="text" name="mail_smtp_host" value="<?php echo e($settings['mail_smtp_host']); ?>" placeholder="例如：smtp.qq.com">
            </div>
            <div class="form-group">
                <label>SMTP 端口</label>
                <input type="number" name="mail_smtp_port" value="<?php echo e((string)$settings['mail_smtp_port']); ?>" placeholder="465" min="1" max="65535">
            </div>
            <div class="form-group">
                <label>加密方式</label>
                <select name="mail_smtp_secure">
                    <?php
                    $secureOpts = ['ssl' => 'SSL（常用端口 465）', 'tls' => 'STARTTLS（常用端口 587）', 'none' => '不加密（端口 25）'];
                    foreach ($secureOpts as $v => $label):
                    ?>
                        <option value="<?php echo e($v); ?>" <?php echo $settings['mail_smtp_secure'] === $v ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>发件邮箱地址</label>
                <input type="email" name="mail_from_address" value="<?php echo e($settings['mail_from_address']); ?>" placeholder="例如：shop@example.com">
                <small>买家收到的邮件中显示的发件地址</small>
            </div>
            <div class="form-group">
                <label>发件人名称（可选）</label>
                <input type="text" name="mail_from_name" value="<?php echo e($settings['mail_from_name']); ?>" placeholder="例如：云谷优选客服">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>SMTP 账号</label>
                <input type="text" name="mail_smtp_user" value="<?php echo e($settings['mail_smtp_user']); ?>" placeholder="通常与发件邮箱相同" autocomplete="off">
            </div>
            <div class="form-group">
                <label>SMTP 密码 / 授权码</label>
                <input type="password" name="mail_smtp_pass" value="<?php echo e($settings['mail_smtp_pass']); ?>" placeholder="多数邮箱需填「授权码」" autocomplete="new-password">
                <small>QQ / 163 等邮箱需在邮箱设置中开启 SMTP 并生成「授权码」，此处填授权码而非登录密码</small>
            </div>
        </div>
        <div class="form-two-col">
            <div class="form-group">
                <label>管理员接收邮箱（多个用逗号分隔）</label>
                <input type="text" name="mail_admin_emails" value="<?php echo e($settings['mail_admin_emails']); ?>" placeholder="admin@example.com,ops@example.com">
                <small>用于接收站点通知邮件</small>
            </div>
            <div class="form-group">
                <label>发送测试</label>
                <div class="inline-with-btn">
                    <input type="email" id="mailTestTo" placeholder="测试收件邮箱" autocomplete="off">
                    <button type="button" class="btn-primary-sm" id="mailTestBtn">发送测试</button>
                </div>
                <small id="mailTestResult"></small>
            </div>
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

    <div class="form-actions">
        <button type="submit" class="btn-primary" id="saveNotifyBtn">保存设置</button>
    </div>
</form>

<script>
    window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
</script>
<?php admin_page_end(); ?>
