<?php
require_once __DIR__ . '/config.php';

/**
 * 全局 PDO 实例
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => APP_ENV === 'development' ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        if (APP_ENV === 'development') {
            exit('数据库连接失败: ' . $e->getMessage());
        }
        error_log('DB connect failed: ' . $e->getMessage());
        exit('系统暂时不可用，请稍后重试。');
    }
    return $pdo;
}

/**
 * 执行 SELECT 返回全部行
 */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 执行 SELECT 返回单行
 */
function db_row(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * 执行 SELECT 返回单个字段
 */
function db_one(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * 执行 INSERT/UPDATE/DELETE
 */
function db_exec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * 最后插入 ID
 */
function db_last_id(): string
{
    return db()->lastInsertId();
}
