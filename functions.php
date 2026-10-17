<?php
require_once __DIR__ . '/db.php';

/**
 * 通用函数与助手
 */

function json_response(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    $rel  = '/assets/' . ltrim($path, '/');
    $file = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $rel;
    $v    = is_file($file) ? @filemtime($file) : 0;
    return rtrim(APP_URL, '/') . $rel . ($v ? '?v=' . $v : '');
}

/**
 * 禁止浏览器缓存当前动态页面（避免部署后前端跑旧 HTML/内联脚本）
 */
function nocache_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function upload_url(string $path): string
{
    if (!$path) return '';
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function now(): int
{
    return time();
}

function format_money(float $amount): string
{
    return number_format($amount, 2);
}

function order_status_label(int $status): string
{
    $map = [
        -1 => '已取消',
        0  => '待付款',
        1  => '已付款',
        2  => '已发货',
        3  => '已完成',
    ];
    return $map[$status] ?? '未知';
}

function order_status_class(int $status): string
{
    $map = [
        -1 => 'muted',
        0  => 'warning',
        1  => 'info',
        2  => 'primary',
        3  => 'success',
    ];
    return $map[$status] ?? 'muted';
}

/**
 * CSRF Token
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $post   = $_POST['csrf_token'] ?? '';
    $token  = $header ?: $post;
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !verify_csrf()) {
        http_response_code(403);
        json_response(['code' => 403, 'msg' => '安全校验失败，请刷新页面重试。']);
    }
}

/**
 * 管理员登录相关
 */
function admin_login(string $username, string $password): bool
{
    if ($username !== ADMIN_USERNAME) {
        return false;
    }
    $hash = setting('admin_password_hash');
    if ($hash) {
        return password_verify($password, $hash);
    }
    return password_verify($password, ADMIN_PASSWORD_HASH);
}

function is_admin(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * 分页助手
 * @param int $total 总记录数
 * @param int $perPage 每页条数
 * @return array ['page'=>当前页, 'offset'=>偏移, 'per_page'=>每页, 'total_pages'=>总页数]
 */
function paginate(int $total, int $perPage = 20): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    return ['page' => $page, 'offset' => $offset, 'per_page' => $perPage, 'total_pages' => $totalPages];
}

/**
 * 渲染分页控件（保留当前 query 参数，仅重置 page）
 * @param array $pg paginate() 返回
 * @param string $baseUrl 当前页基础路径，如 /admin/orders.php
 * @param array $keep 需额外保留的 query 键（如 status/view/keyword 等已含在 $_GET 中亦可不传）
 */
function render_pagination(array $pg, string $baseUrl, array $keep = []): string
{
    if ($pg['total_pages'] <= 1) return '';
    $params = $_GET;
    foreach ($keep as $k) {
        if (isset($_GET[$k])) $params[$k] = $_GET[$k];
    }
    unset($params['page']);
    $build = function (int $p) use ($baseUrl, $params): string {
        $params['page'] = $p;
        return $baseUrl . '?' . http_build_query($params);
    };
    $cur = $pg['page'];
    $pages = [];
    $pages[] = 1;
    for ($p = max(2, $cur - 2); $p <= min($pg['total_pages'] - 1, $cur + 2); $p++) $pages[] = $p;
    if ($pg['total_pages'] > 1) $pages[] = $pg['total_pages'];
    $pages = array_values(array_unique($pages));
    $html = '<div class="pagination">';
    if ($cur > 1) {
        $html .= '<a class="page-item" href="' . e($build($cur - 1)) . '">上一页</a>';
    } else {
        $html .= '<span class="page-item disabled">上一页</span>';
    }
    $prev = 0;
    foreach ($pages as $p) {
        if ($p - $prev > 1) $html .= '<span class="page-item dots">…</span>';
        $active = $p === $cur ? ' is-active' : '';
        $html .= '<a class="page-item' . $active . '" href="' . e($build($p)) . '">' . $p . '</a>';
        $prev = $p;
    }
    if ($cur < $pg['total_pages']) {
        $html .= '<a class="page-item" href="' . e($build($cur + 1)) . '">下一页</a>';
    } else {
        $html .= '<span class="page-item disabled">下一页</span>';
    }
    $html .= '<span class="page-info">共 ' . (int) $pg['total_pages'] . ' 页 · ' . (int) ($_GET['__total__'] ?? 0) . ' 条</span>';
    $html .= '</div>';
    return $html;
}

function admin_logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * 生成订单号
 */
function generate_order_no(): string
{
    return ORDER_NO_PREFIX . date('YmdHis') . substr(microtime(true) * 10000, -4) . mt_rand(10, 99);
}

/**
 * 通用设置读写
 */
function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_all('SELECT `key`, `value` FROM settings');
        $cache = array_column($rows, 'value', 'key');
    }
    return $cache[$key] ?? $default;
}

function setting_set(string $key, string $value): void
{
    $exists = db_one('SELECT 1 FROM settings WHERE `key` = ?', [$key]);
    if ($exists) {
        db_exec('UPDATE settings SET `value` = ? WHERE `key` = ?', [$value, $key]);
    } else {
        db_exec('INSERT INTO settings (`key`, `value`) VALUES (?, ?)', [$key, $value]);
    }
}

function setting_refresh(): void
{
    $rows = db_all('SELECT `key`, `value` FROM settings');
    $GLOBALS['settings_cache'] = array_column($rows, 'value', 'key');
}

/**
 * 图片上传
 */
function upload_image(array $file): array
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'msg' => '未收到上传文件'];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'msg' => '图片大小不能超过 ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, UPLOAD_IMAGE_TYPES, true)) {
        return ['ok' => false, 'msg' => '仅支持 jpg/png/gif/webp 图片'];
    }

    $exts = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext   = $exts[$mime] ?? 'jpg';
    $dir   = UPLOAD_DIR . date('Ym') . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name  = date('dHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path  = $dir . $name;
    $webPath = UPLOAD_URL . date('Ym') . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['ok' => false, 'msg' => '文件保存失败'];
    }

    return ['ok' => true, 'path' => $webPath];
}

/**
 * 验证手机号(中国大陆)
 */
function is_mobile(string $phone): bool
{
    return (bool) preg_match('/^1[3-9]\d{9}$/', $phone);
}

/**
 * 渲染短信模板变量
 */
function render_sms_template(string $tpl, array $vars): string
{
    if ($tpl === '') {
        return '';
    }
    $out = $tpl;
    foreach ($vars as $k => $v) {
        $out = str_replace('{' . $k . '}', (string) $v, $out);
    }
    return $out;
}

/**
 * 发送短信（螺丝帽 LuosiMao）
 * 认证：HTTP Basic Auth，用户名固定 api，密码为 API Key
 */
function send_sms(string $mobile, string $content): array
{
    $apiKey = setting('sms_api_key');
    if (!setting('sms_enabled') || !$apiKey) {
        return ['ok' => false, 'msg' => '短信未开启或未配置 API Key'];
    }
    if (!is_mobile($mobile)) {
        return ['ok' => false, 'msg' => '手机号格式错误'];
    }

    $sigRaw = trim((string) setting('sms_signature', ''));
    $sig    = trim($sigRaw, '【】 ');
    $message = $sig !== '' ? ('【' . $sig . '】' . $content) : $content;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://sms-api.luosimao.com/v1/send.json',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => 'api:' . $apiKey,
        CURLOPT_POSTFIELDS     => http_build_query(['mobile' => $mobile, 'message' => $message]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        error_log('SMS send curl error: ' . $errmsg);
        return ['ok' => false, 'msg' => '网络请求失败'];
    }
    $data = json_decode($resp, true) ?: [];
    if (($data['error'] ?? -99) === 0) {
        return ['ok' => true, 'msg' => '发送成功'];
    }
    return ['ok' => false, 'msg' => ($data['msg'] ?? '未知错误')];
}
