<?php
/**
 * 付款卡片合成：服务端用 GD 生成真实 PNG 图片，返回真实 https 地址。
 * 目的：绕开微信对 blob:/data: 图片长按"保存到相册"的屏蔽——只有真实 http(s) 图片微信才能长按保存。
 */
require_once __DIR__ . '/functions.php';
nocache_headers();

$orderNo = trim($_GET['order_no'] ?? '');
if (!$orderNo || !preg_match('/^IM\d{10,}$/', $orderNo)) {
    outputPlaceholder('订单号无效');
    exit;
}

$order = db_row('SELECT o.*, p.payment_qr AS product_payment_qr FROM orders o LEFT JOIN products p ON p.id = o.product_id WHERE o.order_no = ? AND o.deleted_at IS NULL', [$orderNo]);
if (!$order) {
    outputPlaceholder('订单不存在');
    exit;
}

$qr = !empty($order['product_payment_qr']) ? $order['product_payment_qr'] : setting('payment_qr');
$qrPath = $qr ? __DIR__ . '/' . ltrim($qr, '/') : '';
$qrImg = ($qrPath && file_exists($qrPath)) ? loadImg($qrPath) : false;
if (!$qrImg) {
    outputPlaceholder('收款码未配置');
    exit;
}

$font = __DIR__ . '/assets/fonts/msyh.ttc';
if (!file_exists($font)) {
    outputPlaceholder('字体缺失');
    exit;
}

$W = 680;
$canvasH = 1400; // 先画一个足够高的画布，最后按内容裁剪
$im = imagecreatetruecolor($W, $canvasH);
$cWhite = imagecolorallocate($im, 255, 255, 255);
imagefill($im, 0, 0, $cWhite);
imagealphablending($im, true);
imagesavealpha($im, true);

$cDark  = imagecolorallocate($im, 31, 41, 55);
$cGray  = imagecolorallocate($im, 107, 114, 128);
$cMid   = imagecolorallocate($im, 55, 65, 81);
$cRed   = imagecolorallocate($im, 220, 38, 38);
$cWarm  = imagecolorallocate($im, 248, 200, 168); // #f8c8a8
$cBrand = imagecolorallocate($im, 8, 56, 88);     // #083858

$siteName = setting('site_name', defined('APP_NAME') ? APP_NAME : '扫码支付');

// 流式游标 $y：自上而下排布，各区块间距沿用原版数值，便于以后调整顺序
$y = 40;

// ① 顶部：三个逐渐加深的同心圆 + 长按保存到手机（移到卡片最上面，作为头部）
$cx = $W / 2;
$rOut = 26;
$rMid = 17;
$rIn = 9;
$cLight = imagecolorallocate($im, 203, 217, 230); // #cbd9e6 最浅
$cMed   = imagecolorallocate($im, 91, 139, 176);  // #5b8bb0 中
$cDarkI = imagecolorallocate($im, 8, 56, 88);     // #083858 最深
$iconY = $y + $rOut;                              // 圆心，使外圆顶部贴 $y
imagefilledellipse($im, $cx, $iconY, $rOut * 2, $rOut * 2, $cLight);
imagefilledellipse($im, $cx, $iconY, $rMid * 2, $rMid * 2, $cMed);
imagefilledellipse($im, $cx, $iconY, $rIn * 2,  $rIn * 2,  $cDarkI);
$lpBaseline = $iconY + $rOut + 44;                // 长按文字基线
centerText($im, $font, 20, $cBrand, '长按保存到手机', $lpBaseline);
$y = $lpBaseline + 70;                            // 头部与标题间距

// ② 标题
$productTitle = !empty($order['product_title']) ? $order['product_title'] : $siteName;
centerText($im, $font, 38, $cDark, $productTitle, $y);
$y += 46;                                         // 标题→副标题间距（原 78→124）

// ③ 副标题
centerText($im, $font, 20, $cGray, '请使用微信「扫一扫」付款', $y);
$y += 36;                                         // 副标题→二维码间距（原 124→160）

// ④ 二维码
$qs = 360;
$qx = ($W - $qs) / 2;
imagecopyresampled($im, $qrImg, $qx, $y, 0, 0, $qs, $qs, imagesx($qrImg), imagesy($qrImg));
imagedestroy($qrImg);
$y += $qs;                                        // 二维码底部

// ⑤ 金额（与二维码拉开间距，原 520→606 的 86）
$y += 86;
$amount = '¥' . format_money($order['total_amount']);
centerText($im, $font, 56, $cRed, $amount, $y);
$y += 60;                                         // 金额→手机号区块间距（原 606→666）

// ⑥ 手机号暖色通栏底托
$phone = (string)$order['buyer_phone'];
$blockX = 40;
$blockW = $W - 80;
$blockH = 150;
roundRect($im, $blockX, $y, $blockW, $blockH, 20, $cWarm, $cWarm);
centerText($im, $font, 22, $cBrand, '转账时请备注此下单手机号', $y + 48);
centerText($im, $font, 48, $cBrand, $phone, $y + 124);
$y += $blockH;

// ⑦ 提示文案
$y += 46;
$tip = setting('payment_tip', '保存本卡片到相册，微信「扫一扫→相册」识别付款；支付后请等待后台确认收款。');
$y = wrapText($im, $font, 22, $cMid, $tip, $W, $y, $W - 80, 38);

// ⑧ 后台自定义其他信息
$extra = setting('payment_card_extra', '');
if ($extra) {
    $y += 30;
    $y = wrapText($im, $font, 20, $cGray, $extra, $W, $y, $W - 80, 36);
}

$bottomY = $y + 40;
$targetH = max(1080, $bottomY);

if ($targetH < $canvasH) {
    $cropped = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => $W, 'height' => $targetH]);
    if ($cropped) {
        imagedestroy($im);
        $im = $cropped;
        imagealphablending($im, true);
        imagesavealpha($im, true);
    }
}

// 落盘为真实静态文件，并 302 跳转到其 https 地址（微信长按保存最稳）
$ym = date('Ym');
$dir = __DIR__ . '/uploads/' . $ym;
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$rel = 'uploads/' . $ym . '/pay_card_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNo) . '.png';
$out = __DIR__ . '/' . $rel;
imagepng($im, $out);
imagedestroy($im);

header('Location: ' . upload_url($rel), true, 302);
exit;

function centerText($im, $font, $size, $color, $text, $y)
{
    global $W;
    $box = imagettfbbox($size, 0, $font, $text);
    $w = $box[2] - $box[0];
    $x = ($W - $w) / 2;
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function roundRect($im, $x, $y, $w, $h, $r, $fill, $stroke)
{
    $r = min($r, $w / 2, $h / 2);
    imagefilledrectangle($im, $x + $r, $y, $x + $w - $r, $y + $h, $fill);
    imagefilledrectangle($im, $x, $y + $r, $x + $w, $y + $h - $r, $fill);
    imagefilledellipse($im, $x + $r, $y + $r, $r * 2, $r * 2, $fill);
    imagefilledellipse($im, $x + $w - $r, $y + $r, $r * 2, $r * 2, $fill);
    imagefilledellipse($im, $x + $r, $y + $h - $r, $r * 2, $r * 2, $fill);
    imagefilledellipse($im, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $fill);
    if ($fill !== $stroke) {
        imageline($im, $x + $r, $y, $x + $w - $r, $y, $stroke);
        imageline($im, $x + $r, $y + $h, $x + $w - $r, $y + $h, $stroke);
        imageline($im, $x, $y + $r, $x, $y + $h - $r, $stroke);
        imageline($im, $x + $w, $y + $r, $x + $w, $y + $h - $r, $stroke);
    }
}

function wrapText($im, $font, $size, $color, $text, $W, $startY, $maxW, $lh)
{
    $text = (string)$text;
    if ($text === '') {
        return $startY;
    }
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $line = '';
    $y = $startY;
    foreach ($chars as $ch) {
        if ($ch === "\n") {
            renderLine($im, $font, $size, $color, $line, $W, $y);
            $line = '';
            $y += $lh;
            continue;
        }
        $box = imagettfbbox($size, 0, $font, $line . $ch);
        $w = $box[2] - $box[0];
        if ($w > $maxW && $line !== '') {
            renderLine($im, $font, $size, $color, $line, $W, $y);
            $line = $ch;
            $y += $lh;
        } else {
            $line .= $ch;
        }
    }
    if ($line !== '') {
        renderLine($im, $font, $size, $color, $line, $W, $y);
        $y += $lh;
    }
    return $y;
}

function renderLine($im, $font, $size, $color, $text, $W, $y)
{
    if ($text === '') {
        return;
    }
    $box = imagettfbbox($size, 0, $font, $text);
    $w = $box[2] - $box[0];
    $x = ($W - $w) / 2;
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function loadImg($path)
{
    $info = @getimagesize($path);
    if (!$info) {
        return false;
    }
    switch ($info[2]) {
        case IMAGETYPE_PNG:
            return imagecreatefrompng($path);
        case IMAGETYPE_JPEG:
            return imagecreatefromjpeg($path);
        case IMAGETYPE_GIF:
            return imagecreatefromgif($path);
        case IMAGETYPE_WEBP:
            return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false;
        default:
            return false;
    }
}

function outputPlaceholder($msg)
{
    $font = __DIR__ . '/assets/fonts/msyh.ttc';
    $W = 680;
    $im = imagecreatetruecolor($W, 1080);
    $w = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $w);
    $g = imagecolorallocate($im, 156, 163, 175);
    if (file_exists($font)) {
        $box = imagettfbbox(22, 0, $font, $msg);
        $tw = $box[2] - $box[0];
        $tx = ($W - $tw) / 2;
        imagettftext($im, 22, 0, $tx, 540, $g, $font, $msg);
    }
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    imagepng($im);
    imagedestroy($im);
}
