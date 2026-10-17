<?php
/**
 * iMall 单品商城配置文件
 * 部署后请勿将本文件暴露到版本控制。
 * 本仓库内为脱敏模板，部署时请替换为你的真实配置。
 */

define('APP_ENV', 'production');      // production | development
define('APP_URL', 'https://your-domain.com');
define('APP_NAME', 'iMall 单品商城');
define('APP_VERSION', '1.0.0');

// 数据库（部署时请替换为你的真实配置）
 define('DB_HOST', '127.0.0.1');
 define('DB_PORT', 3306);
 define('DB_NAME', 'imall_shop');
 define('DB_USER', 'imall_shop');
 define('DB_PASS', 'CHANGE_ME_DB_PASSWORD');
 define('DB_CHARSET', 'utf8mb4');

// 管理员账号(登录后台后可在「系统设置」修改密码)
 define('ADMIN_USERNAME', 'admin');
 define('ADMIN_PASSWORD_HASH', '$2b$12$prgddM2r6tjOmfKoUtxHouPQte0Q.tZvT3cZdWbkQTUKJG3QSyOYq'); // CJrUPP3UWMgd

// 上传
 define('UPLOAD_DIR', __DIR__ . '/uploads/');
 define('UPLOAD_URL', '/uploads/');
 define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
 define('UPLOAD_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// 订单
 define('ORDER_NO_PREFIX', 'IM');

// 会话
 session_set_cookie_params([
     'lifetime' => 86400,
     'path'     => '/',
     'secure'   => false,
     'httponly' => true,
     'samesite' => 'Lax',
 ]);
