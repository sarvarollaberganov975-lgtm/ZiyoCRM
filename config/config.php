<?php
// ==============================================
// ZiyoCRM - Konfiguratsiya fayli
// ==============================================

if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $tmp_dir = '/tmp/database';
    if (!is_dir($tmp_dir)) @mkdir($tmp_dir, 0777, true);
    $tmp_db = $tmp_dir . '/ziyo_crm.db';
    $source_db = __DIR__ . '/../database/ziyo_crm.db';
    if (!file_exists($tmp_db) && file_exists($source_db)) {
        @copy($source_db, $tmp_db);
    }
    define('DB_FILE', file_exists($tmp_db) ? $tmp_db : __DIR__ . '/../database/ziyo_crm.db');
} else {
    define('DB_FILE', __DIR__ . '/../database/ziyo_crm.db');
}

// === TELEGRAM BOT SOZLAMALARI ===
// BotFather dan olgan token ni shu yerga yozing:
define('TELEGRAM_BOT_TOKEN', '8915753990:AAG7MmX_tAxQYqWKOIWACsgiFXXhBDfrBdY');
define('TELEGRAM_BOT_USERNAME', 'ziyo_crm_bot');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/');

// === SESSIYA ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === MA'LUMOTLAR BAZASIGA ULANISH ===
function getDB() {
    static $db = null;
    if ($db === null) {
        $dir = dirname(DB_FILE);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        initDatabase($db);
    }
    return $db;
}

// === JADVALLARNI YARATISH ===
function initDatabase($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin','teacher','student','parent')),
        telegram_chat_id TEXT,
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER DEFAULT 1
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        subject TEXT,
        teacher_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(teacher_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS student_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        group_id INTEGER NOT NULL,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES users(id),
        FOREIGN KEY(group_id) REFERENCES groups(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS parent_student (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER NOT NULL,
        student_id INTEGER NOT NULL,
        FOREIGN KEY(parent_id) REFERENCES users(id),
        FOREIGN KEY(student_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS warnings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        teacher_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        description TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notified_student INTEGER DEFAULT 0,
        notified_parent INTEGER DEFAULT 0,
        FOREIGN KEY(student_id) REFERENCES users(id),
        FOREIGN KEY(teacher_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        group_id INTEGER NOT NULL,
        date DATE NOT NULL,
        status TEXT NOT NULL CHECK(status IN ('present','absent','late')),
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES users(id),
        FOREIGN KEY(group_id) REFERENCES groups(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        month TEXT NOT NULL,
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending','paid','overdue')),
        note TEXT,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS homeworks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_id INTEGER NOT NULL,
        teacher_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        due_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(group_id) REFERENCES groups(id),
        FOREIGN KEY(teacher_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        author_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        target TEXT DEFAULT 'all' CHECK(target IN ('all','students','teachers','parents')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(author_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS telegram_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id TEXT,
        message TEXT,
        status TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS qr_logins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE NOT NULL,
        chat_id TEXT,
        user_id INTEGER,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");

    // === COINS & GAMIFICATION TABLES ===
    $db->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS test_levels (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subject_id INTEGER NOT NULL,
        level_number INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        reward_coins INTEGER DEFAULT 2,
        passing_score INTEGER DEFAULT 80,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(subject_id) REFERENCES subjects(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS test_questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level_id INTEGER NOT NULL,
        question_text TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        correct_option TEXT NOT NULL CHECK(correct_option IN ('A','B','C','D')),
        explanation TEXT,
        FOREIGN KEY(level_id) REFERENCES test_levels(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS student_coins (
        student_id INTEGER PRIMARY KEY,
        coins_balance INTEGER DEFAULT 0,
        total_earned INTEGER DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS student_level_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        level_id INTEGER NOT NULL,
        score_percent INTEGER NOT NULL,
        passed INTEGER DEFAULT 0,
        coins_awarded INTEGER DEFAULT 0,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES users(id),
        FOREIGN KEY(level_id) REFERENCES test_levels(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS shop_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        image_url TEXT,
        coin_price INTEGER NOT NULL,
        cash_price REAL DEFAULT 0,
        stock_quantity INTEGER DEFAULT 10,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gift_redemptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        coins_spent INTEGER NOT NULL,
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending','approved','delivered','rejected')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME,
        note TEXT,
        FOREIGN KEY(student_id) REFERENCES users(id),
        FOREIGN KEY(item_id) REFERENCES shop_items(id)
    )");

    // === TARGET CRM TABLES ===
    $db->exec("CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        phone TEXT NOT NULL,
        source TEXT DEFAULT 'Instagram' CHECK(source IN ('Instagram','Facebook','Telegram','Website','Manual','Other')),
        subject_interest TEXT,
        stage TEXT DEFAULT 'new' CHECK(stage IN ('new','contacted','trial_scheduled','enrolled','lost')),
        notes TEXT,
        assigned_to INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(assigned_to) REFERENCES users(id)
    )");

    // Admin foydalanuvchisini yaratish (agar mavjud bo'lmasa)
    $admin = $db->query("SELECT id FROM users WHERE username='azamat'")->fetch();
    if (!$admin) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)")
           ->execute(['azamat', $hash, 'Azamat Admin', 'admin']);
    }

    // Boshlang'ich fanlar
    $subjCheck = $db->query("SELECT COUNT(*) as cnt FROM subjects")->fetch();
    if ($subjCheck['cnt'] == 0) {
        $db->exec("INSERT INTO subjects (name, description) VALUES 
            ('Ingliz tili (Grammar)', 'Ingliz tili grammatikasi va lugat testi'),
            ('Matematika & Mantiq', 'Matematik masalalar va mantiqiy testlar'),
            ('IT & Dasturlash (Python)', 'Dasturlash asoslari va algoritm testlari')");
    }
}

// === TELEGRAM XABAR YUBORISH ===
function sendTelegram($chat_id, $message) {
    if (empty($chat_id) || TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') return false;
    
    $url = TELEGRAM_API_URL . 'sendMessage';
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    // Logga yozish
    try {
        $db = getDB();
        $db->prepare("INSERT INTO telegram_logs (chat_id, message, status) VALUES (?, ?, ?)")
           ->execute([$chat_id, $message, $result ? 'sent' : 'failed']);
    } catch(Exception $e) {}
    
    return $result !== false;
}

// === FOYDALANUVCHI TEKSHIRISH ===
function requireLogin($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        header('Location: ../index.php?error=access_denied');
        exit;
    }
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
