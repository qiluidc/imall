<?php
require_once __DIR__ . '/../../functions.php';
require_admin();
require_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['code' => 405, 'msg' => '请求方式错误']);
}

if (empty($_FILES['file'])) {
    json_response(['code' => 400, 'msg' => '未收到文件']);
}

$result = upload_image($_FILES['file']);
if (!$result['ok']) {
    json_response(['code' => 400, 'msg' => $result['msg']]);
}

json_response(['code' => 0, 'msg' => '上传成功', 'data' => ['path' => $result['path'], 'url' => upload_url($result['path'])]]);
