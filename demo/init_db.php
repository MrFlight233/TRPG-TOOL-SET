<?php
// 初始化 SQLite 数据库
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$db_path = __DIR__ . '/loot.db';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 创建 players 表
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );");

    // 创建 loots 表
    $db->exec("CREATE TABLE IF NOT EXISTS loots (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        price REAL NOT NULL DEFAULT 0,
        type TEXT NOT NULL DEFAULT '战利品',
        owner TEXT,
        sold TEXT NOT NULL DEFAULT 'false',
        acquiredDate TEXT,
        note TEXT,
        createDate TEXT NOT NULL
    );");

    echo json_encode([
        'status' => 'success',
        'message' => '数据库初始化成功',
        'db_path' => $db_path
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '数据库初始化失败: ' . $e->getMessage(),
        'db_path' => $db_path
    ]);
}
?>