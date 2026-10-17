<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$mobile = trim($input['mobile'] ?? '');
if (!is_mobile($mobile)) {
    json_response(['code' => 400, 'msg' => '请填写正确的手机号']);
}

$apiKey = setting('sms_api_key');
if (!setting('sms_enabled') || !$apiKey) {
    json_response(['code' => 400, 'msg' => '请先在「通知设置」填写 API Key 并启用短信通知']);
}

$content = '短信通道测试：您好，这是来自 ' . setting('site_name', APP_NAME) . ' 的一条测试短信，收到说明配置正常。';
$res = send_sms($mobile, $content);
json_response([
    'code' => $res['ok'] ? 0 : 1,
    'msg'  => $res['ok'] ? '发送成功，请查收手机短信' : ('发送失败：' . $res['msg']),
]);
