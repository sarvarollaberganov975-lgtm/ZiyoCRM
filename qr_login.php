<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'generate') {
    $db = getDB();
    
    // Eski tokenlarni to'zalash (10 minutdan o'tgan)
    $db->exec("DELETE FROM qr_logins WHERE datetime(expires_at) < datetime('now') OR status = 'expired'");
    
    // Yangi token yaratish
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minut amal qiladi
    
    $stmt = $db->prepare("INSERT INTO qr_logins (token, expires_at) VALUES (?, ?)");
    $stmt->execute([$token, $expires_at]);
    
    $botUsername = TELEGRAM_BOT_USERNAME;
    $telegramUrl = "https://t.me/{$botUsername}?start=qr_login_{$token}";
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'telegram_url' => $telegramUrl
    ]);
    exit;
}

if ($action === 'check') {
    $token = $_GET['token'] ?? '';
    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'Token required']);
        exit;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM qr_logins WHERE token = ?");
    $stmt->execute([$token]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$qr) {
        echo json_encode(['success' => false, 'status' => 'not_found']);
        exit;
    }
    
    if (strtotime($qr['expires_at']) < time()) {
        echo json_encode(['success' => false, 'status' => 'expired']);
        exit;
    }
    
    if ($qr['status'] === 'approved' && !empty($qr['user_id'])) {
        // Userni topish
        $uStmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $uStmt->execute([$qr['user_id']]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Sessiyani yoqish!
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            
            // Ishlatilgan tokenni ochirish
            $db->prepare("DELETE FROM qr_logins WHERE id = ?")->execute([$qr['id']]);
            
            $map = [
                'admin'   => '/admin/dashboard.php',
                'teacher' => '/teacher/dashboard.php',
                'student' => '/student/dashboard.php',
                'parent'  => '/parent/dashboard.php'
            ];
            $redirectUrl = $map[$user['role']] ?? '/index.php';
            
            echo json_encode([
                'success' => true,
                'status' => 'approved',
                'redirect' => $redirectUrl
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => $qr['status'] // 'pending', 'rejected', 'expired'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
