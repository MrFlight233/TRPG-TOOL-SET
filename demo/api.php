<?php
// API 接口脚本
// 处理所有请求的 CORS 头信息
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 初始化数据库连接
$db_path = __DIR__ . '/loot.db';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $e->getMessage()]);
    exit;
}

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 处理操作
switch ($action) {
    // 初始化数据库
    case 'init':
        // 使用已有的 $db 连接执行数据库初始化代码
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
        break;
    
    // 获取所有 loot
    case 'get_loots':
        $stmt = $db->query('SELECT * FROM loots ORDER BY createDate DESC');
        $loots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $loots]);
        break;
    
    // 获取所有 players
    case 'get_players':
        $stmt = $db->query('SELECT name FROM players ORDER BY id');
        $players = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['status' => 'success', 'data' => $players]);
        break;
    
    // 添加 loot
    case 'add_loot':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'], $data['name'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
            break;
        }
        
        $stmt = $db->prepare("INSERT INTO loots (id, name, quantity, price, type, owner, sold, acquiredDate, note, createDate)
            VALUES (:id, :name, :quantity, :price, :type, :owner, :sold, :acquiredDate, :note, :createDate)");
        
        $stmt->execute([
            ':id' => $data['id'],
            ':name' => $data['name'],
            ':quantity' => $data['quantity'] ?? 1,
            ':price' => $data['price'] ?? 0,
            ':type' => $data['type'] ?? '战利品',
            ':owner' => $data['owner'],
            ':sold' => $data['sold'] ?? 'false',
            ':acquiredDate' => $data['acquiredDate'],
            ':note' => $data['note'],
            ':createDate' => $data['createDate'] ?? date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['status' => 'success', 'message' => '添加成功']);
        break;
    
    // 更新 loot
    case 'update_loot':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
            break;
        }
        
        $stmt = $db->prepare("UPDATE loots SET
                name = :name,
                quantity = :quantity,
                price = :price,
                type = :type,
                owner = :owner,
                sold = :sold,
                acquiredDate = :acquiredDate,
                note = :note
            WHERE id = :id");
        
        $stmt->execute([
            ':id' => $data['id'],
            ':name' => $data['name'],
            ':quantity' => $data['quantity'],
            ':price' => $data['price'],
            ':type' => $data['type'],
            ':owner' => $data['owner'],
            ':sold' => $data['sold'],
            ':acquiredDate' => $data['acquiredDate'],
            ':note' => $data['note']
        ]);
        
        echo json_encode(['status' => 'success', 'message' => '更新成功']);
        break;
    
    // 删除 loot (使用 GET 请求)
    case 'delete_loot':
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => '缺少 ID 参数']);
            break;
        }
        
        $stmt = $db->prepare('DELETE FROM loots WHERE id = :id');
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['status' => 'success', 'message' => '删除成功']);
        break;
    
    // 删除 loot (使用 POST 请求，备用方案)
    case 'delete_loot_post':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? $data['id'] : '';
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => '缺少 ID 参数']);
            break;
        }
        
        $stmt = $db->prepare('DELETE FROM loots WHERE id = :id');
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['status' => 'success', 'message' => '删除成功']);
        break;
    
    // 添加 player
    case 'add_player':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['name'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
            break;
        }
        
        try {
            $stmt = $db->prepare('INSERT INTO players (name) VALUES (:name)');
            $stmt->execute([':name' => $data['name']]);
            echo json_encode(['status' => 'success', 'message' => '添加成功']);
        } catch (Exception $e) {
            // 处理唯一约束错误
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                echo json_encode(['status' => 'error', 'message' => '玩家名称已存在']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '添加失败: ' . $e->getMessage()]);
            }
        }
        break;
    
    // 删除 player
    case 'delete_player':
        $name = isset($_GET['name']) ? $_GET['name'] : '';
        if (!$name) {
            echo json_encode(['status' => 'error', 'message' => '缺少名称参数']);
            break;
        }
        
        // 先删除该玩家的所有 loot 归属
        $db->prepare('UPDATE loots SET owner = NULL WHERE owner = :name')->execute([':name' => $name]);
        
        // 再删除玩家
        $stmt = $db->prepare('DELETE FROM players WHERE name = :name');
        $stmt->execute([':name' => $name]);
        
        echo json_encode(['status' => 'success', 'message' => '删除成功']);
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
        break;
}
?>