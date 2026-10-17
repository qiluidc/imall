<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$siteName = trim($input['site_name'] ?? '');
$siteNotice = trim($input['site_notice'] ?? '');
$paymentQr = trim($input['payment_qr'] ?? '');
$paymentTip = trim($input['payment_tip'] ?? '');
$paymentCardExtra = trim($input['payment_card_extra'] ?? '');
$contactPhone = trim($input['contact_phone'] ?? '');
$contactWechat = trim($input['contact_wechat'] ?? '');
$contactWechatQr = trim($input['contact_wechat_qr'] ?? '');
$smsEnabled = !empty($input['sms_enabled']) ? '1' : '0';
$smsApiKey = trim($input['sms_api_key'] ?? '');
$smsSignature = trim($input['sms_signature'] ?? '');
$adminMobiles = trim($input['admin_notify_mobiles'] ?? '');
$smsTplOrder = trim($input['sms_tpl_order'] ?? '');
$smsTplShip = trim($input['sms_tpl_ship'] ?? '');
if ($smsTplOrder === '') {
    $smsTplOrder = '新订单待处理：{product} x{quantity}，合计¥{amount}。收货人：{buyer}({phone})，地址：{address}。订单号：{order_no}';
}
if ($smsTplShip === '') {
    $smsTplShip = '您的订单({order_no})已发货，{company} 单号：{tracking_no}。请留意查收，感谢购买！';
}
$adminPassword = $input['admin_password'] ?? '';
$adminPasswordConfirm = $input['admin_password_confirm'] ?? '';

if ($siteName === '') {
    json_response(['code' => 400, 'msg' => '站点名称不能为空']);
}

setting_set('site_name', $siteName);
setting_set('site_notice', $siteNotice);
setting_set('payment_qr', $paymentQr);
setting_set('payment_tip', $paymentTip);
setting_set('payment_card_extra', $paymentCardExtra);
setting_set('contact_phone', $contactPhone);
setting_set('contact_wechat', $contactWechat);
setting_set('contact_wechat_qr', $contactWechatQr);
setting_set('sms_enabled', $smsEnabled);
setting_set('sms_api_key', $smsApiKey);
setting_set('sms_signature', $smsSignature);
setting_set('admin_notify_mobiles', $adminMobiles);
setting_set('sms_tpl_order', $smsTplOrder);
setting_set('sms_tpl_ship', $smsTplShip);

if ($adminPassword !== '' || $adminPasswordConfirm !== '') {
    if (strlen($adminPassword) < 6) {
        json_response(['code' => 400, 'msg' => '新密码长度不能少于 6 位']);
    }
    if ($adminPassword !== $adminPasswordConfirm) {
        json_response(['code' => 400, 'msg' => '两次输入的密码不一致']);
    }
    $newHash = password_hash($adminPassword, PASSWORD_BCRYPT);
    setting_set('admin_password_hash', $newHash);
}

json_response(['code' => 0, 'msg' => '保存成功']);
