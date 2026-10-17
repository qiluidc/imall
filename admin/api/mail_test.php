<?php
/**
 * 邮件通道测试：向指定邮箱发送一封测试邮件
 */
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$to = trim($input['email'] ?? '');
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    json_response(['code' => 400, 'msg' => '请填写正确的测试收件邮箱']);
}

if (!setting('mail_enabled')) {
    json_response(['code' => 400, 'msg' => '请先在「通知设置」开启邮件通知并保存']);
}

$siteName = setting('site_name', APP_NAME);
$subject  = '[' . $siteName . '] 邮件通道测试';
$body     = "这是一封来自「" . $siteName . "」的测试邮件。\n\n"
          . "收到本邮件，说明发件邮箱与 SMTP 配置正确，邮件通道可用。\n\n"
          . "发送时间：" . date('Y-m-d H:i:s') . "\n";

$res = send_mail($to, $subject, $body);
json_response([
    'code' => $res['ok'] ? 0 : 1,
    'msg'  => $res['ok'] ? '发送成功，请查收邮箱（如未收到请检查垃圾邮件）' : ('发送失败：' . $res['msg']),
]);
