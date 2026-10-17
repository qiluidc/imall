<?php
/**
 * 通知设置保存（邮件 + 短信）
 * 与系统设置保存分开，互不覆写：本接口只处理通知相关字段
 */
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// 邮件通知
$mailEnabled = !empty($input['mail_enabled']) ? '1' : '0';
$mailHost    = trim($input['mail_smtp_host'] ?? '');
$mailPort    = (int) ($input['mail_smtp_port'] ?? 465);
$mailSecure  = trim($input['mail_smtp_secure'] ?? 'ssl');
$mailUser    = trim($input['mail_smtp_user'] ?? '');
$mailPass    = (string) ($input['mail_smtp_pass'] ?? '');
$mailFrom    = trim($input['mail_from_address'] ?? '');
$mailName    = trim($input['mail_from_name'] ?? '');
$mailAdmins  = trim($input['mail_admin_emails'] ?? '');

// 短信通知
$smsEnabled = !empty($input['sms_enabled']) ? '1' : '0';
$smsApiKey  = trim($input['sms_api_key'] ?? '');
$smsSign    = trim($input['sms_signature'] ?? '');
$adminMobs  = trim($input['admin_notify_mobiles'] ?? '');
$smsTplOrder = trim($input['sms_tpl_order'] ?? '');
$smsTplShip  = trim($input['sms_tpl_ship'] ?? '');

if ($smsTplOrder === '') {
    $smsTplOrder = '新订单待处理：{product} x{quantity}，合计¥{amount}。收货人：{buyer}({phone})，地址：{address}。订单号：{order_no}';
}
if ($smsTplShip === '') {
    $smsTplShip = '您的订单({order_no})已发货，{company} 单号：{tracking_no}。请留意查收，感谢购买！';
}

// 校验：端口范围、加密方式白名单、邮箱格式
if ($mailHost !== '' && ($mailPort < 1 || $mailPort > 65535)) {
    json_response(['code' => 400, 'msg' => 'SMTP 端口需在 1 ~ 65535 之间']);
}
if (!in_array($mailSecure, ['ssl', 'tls', 'none'], true)) {
    $mailSecure = 'ssl';
}
if ($mailFrom !== '' && !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
    json_response(['code' => 400, 'msg' => '发件邮箱地址格式不正确']);
}
// 管理员接收邮箱逐个校验（空值允许，表示不接收）
$mailAdminList = $mailAdmins === '' ? [] : (preg_split('/[\s,;，、]+/u', $mailAdmins, -1, PREG_SPLIT_NO_EMPTY) ?: []);
foreach ($mailAdminList as $addr) {
    if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
        json_response(['code' => 400, 'msg' => '管理员接收邮箱中存在无效地址：' . $addr]);
    }
}

setting_set('mail_enabled', $mailEnabled);
setting_set('mail_smtp_host', $mailHost);
setting_set('mail_smtp_port', (string) $mailPort);
setting_set('mail_smtp_secure', $mailSecure);
setting_set('mail_smtp_user', $mailUser);
setting_set('mail_smtp_pass', $mailPass);
setting_set('mail_from_address', $mailFrom);
setting_set('mail_from_name', $mailName);
setting_set('mail_admin_emails', $mailAdmins);

setting_set('sms_enabled', $smsEnabled);
setting_set('sms_api_key', $smsApiKey);
setting_set('sms_signature', $smsSign);
setting_set('admin_notify_mobiles', $adminMobs);
setting_set('sms_tpl_order', $smsTplOrder);
setting_set('sms_tpl_ship', $smsTplShip);

json_response(['code' => 0, 'msg' => '保存成功']);
