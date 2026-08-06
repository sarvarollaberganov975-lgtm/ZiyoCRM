<?php
// ============================================================
// ZiyoCRM BOT — Long Polling (Lokal ishlatish uchun)
// Ishga tushirish: php bot\polling.php
// To'xtatish: Ctrl+C
// ============================================================

// config.php ni yuklash
define('DS', DIRECTORY_SEPARATOR);
require_once __DIR__ . DS . '..' . DS . 'includes' . DS . 'config.php';

echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║   🏫 ZiyoCRM BOT — Polling Mode   ║\n";
echo "║   Bot: @" . TELEGRAM_BOT_USERNAME . "             ║\n";
echo "╚══════════════════════════════════════════╝\n";
echo "\n";

// Bot ma'lumotlarini tekshirish
$meJson = @file_get_contents(TELEGRAM_API_URL . 'getMe');
$me = json_decode($meJson, true);

if (!$me || !$me['ok']) {
    echo "❌ BOT TOKENI NOTO'G'RI!\n";
    echo "   Token: " . TELEGRAM_BOT_TOKEN . "\n";
    echo "   includes/config.php faylini tekshiring.\n";
    exit(1);
}

$botName = $me['result']['first_name'];
$botUser = $me['result']['username'];
echo "✅ Bot ulandi: {$botName} (@{$botUser})\n";
echo "🔄 Xabarlar kutilmoqda... (To'xtatish: Ctrl+C)\n";
echo str_repeat("─", 50) . "\n\n";

// Webhook ni o'chirish (polling bilan bir vaqtda ishlamaydi)
@file_get_contents(TELEGRAM_API_URL . 'deleteWebhook');

$offset = 0;
$errorCount = 0;

while (true) {
    try {
        $url = TELEGRAM_API_URL . 'getUpdates?offset=' . $offset . '&timeout=25&allowed_updates=["message","callback_query"]';

        $context = stream_context_create([
            'http' => [
                'timeout' => 35,
            ]
        ]);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $errorCount++;
            echo "[" . date('H:i:s') . "] ⚠️  Internetga ulanib bo'lmadi (#{$errorCount}). 5 soniyada qayta...\n";
            sleep(5);
            continue;
        }

        $errorCount = 0;
        $data = json_decode($response, true);

        if (!$data || !$data['ok']) {
            echo "[" . date('H:i:s') . "] ❌ API xatosi: " . json_encode($data) . "\n";
            sleep(3);
            continue;
        }

        $updates = $data['result'] ?? [];

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;
            processUpdate($update);
        }

    } catch (Throwable $e) {
        echo "[" . date('H:i:s') . "] ❌ Xato: " . $e->getMessage() . "\n";
        sleep(3);
    }
}

// ============================================================
// UPDATE NI ISHLOVCHI
// ============================================================
function processUpdate($update) {
    $message  = $update['message']        ?? null;
    $callback = $update['callback_query'] ?? null;

    if ($message) {
        $chat_id  = $message['chat']['id'];
        $text     = trim($message['text'] ?? '');
        $fname    = $message['from']['first_name'] ?? 'Foydalanuvchi';

        echo "[" . date('H:i:s') . "] 📨 [{$chat_id}] {$fname}: " . mb_substr($text, 0, 50) . "\n";

        handleMessage($message);

    } elseif ($callback) {
        $chat_id = $callback['message']['chat']['id'];
        $data    = $callback['data'];
        $fname   = $callback['from']['first_name'] ?? '';

        echo "[" . date('H:i:s') . "] 🔘 [{$chat_id}] {$fname}: callback={$data}\n";

        handleCallback($callback);
    }
}

// ============================================================
// XABAR ISHLOVCHI
// ============================================================
function handleMessage($msg) {
    $chat_id = $msg['chat']['id'];
    $text    = trim($msg['text'] ?? '');
    $fname   = $msg['from']['first_name'] ?? '';

    $state = getBotState($chat_id);

    // ─── BUYRUQLAR ───
    if (strpos($text, '/start qr_login_') === 0) {
        $token = trim(str_replace('/start qr_login_', '', $text));
        handleQrLoginStart($chat_id, $token, $fname);
        return;
    }
    if ($text === '/start') {
        clearBotState($chat_id);
        sendWelcome($chat_id, $fname);
        return;
    }
    if ($text === '/help') {
        sendHelp($chat_id);
        return;
    }
    if ($text === '/myid') {
        sendMsg($chat_id, "🆔 Sizning Telegram Chat ID:\n<code>{$chat_id}</code>\n\n📌 Adminга bering, u sizni tizimga ulashi mumkin.");
        return;
    }
    if ($text === '/link') {
        clearBotState($chat_id);
        setBotState($chat_id, ['step' => 'await_username']);
        sendMsg($chat_id, "🔗 <b>Akkauntni ulash</b>\n\nTizim foydalanuvchi nomingizni kiriting (username):");
        return;
    }
    if ($text === '/cancel' || $text === '❌ Bekor qilish') {
        clearBotState($chat_id);
        sendMsg($chat_id, "❌ Amal bekor qilindi.", mainKeyboard($chat_id));
        return;
    }

    // ─── STATE ───
    if ($state) {
        handleState($chat_id, $text, $state);
        return;
    }

    // ─── MENYU ───
    switch ($text) {
        case "📊 Mening ma'lumotlarim":
            sendMyInfo($chat_id);
            break;
        case '⚠️ Ogohlantirishlar':
            sendMyWarnings($chat_id);
            break;
        case '📅 Davomat':
            sendMyAttendance($chat_id);
            break;
        case "💰 To'lov":
            sendMyPayment($chat_id);
            break;
        case '📚 Darslar':
            sendMyLessons($chat_id);
            break;
        case '🆔 ID olish':
            sendMsg($chat_id, "🆔 Sizning Chat ID: <code>{$chat_id}</code>");
            break;
        default:
            if (!isLinked($chat_id)) {
                sendWelcome($chat_id, $fname);
            } else {
                sendMsg($chat_id, "❓ Tushunmadim. Menyudan foydalaning.", mainKeyboard($chat_id));
            }
    }
}

// ============================================================
// CALLBACK
// ============================================================
function handleCallback($cb) {
    $chat_id = $cb['message']['chat']['id'];
    $data    = $cb['data'];
    $fname   = $cb['from']['first_name'] ?? '';

    answerCb($cb['id']);

    if (strpos($data, 'qr_approve_') === 0) {
        $token = str_replace('qr_approve_', '', $data);
        handleQrLoginResponse($chat_id, $token, true);
        return;
    }
    if (strpos($data, 'qr_reject_') === 0) {
        $token = str_replace('qr_reject_', '', $data);
        handleQrLoginResponse($chat_id, $token, false);
        return;
    }

    if ($data === 'link_account') {
        clearBotState($chat_id);
        setBotState($chat_id, ['step' => 'await_username']);
        sendMsg($chat_id, "🔗 <b>Akkauntni ulash</b>\n\nTizim foydalanuvchi nomingizni kiriting:");
        return;
    }
    if ($data === 'get_id') {
        sendMsg($chat_id, "🆔 Sizning Telegram Chat ID:\n<code>{$chat_id}</code>\n\n📌 Adminга berib, uni tizimda akkauntingizga ulashini so'rang.");
        return;
    }
    if ($data === 'my_warnings')    { sendMyWarnings($chat_id);   return; }
    if ($data === 'my_attendance')  { sendMyAttendance($chat_id); return; }
}

// ============================================================
// STATE MACHINE
// ============================================================
function handleState($chat_id, $text, $state) {
    $step = $state['step'] ?? '';

    if ($step === 'await_username') {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name, role FROM users WHERE username=? AND is_active=1");
        $stmt->execute([$text]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            sendMsg($chat_id, "❌ <b>Foydalanuvchi topilmadi!</b>\n\n<code>{$text}</code> nomli foydalanuvchi mavjud emas.\n\nQayta urinib ko'ring yoki admin bilan bog'laning.");
            return;
        }

        setBotState($chat_id, ['step' => 'await_password', 'username' => $text, 'user_id' => $user['id']]);
        sendMsg($chat_id, "✅ Foydalanuvchi topildi: <b>{$user['full_name']}</b>\n\nEndi parolingizni kiriting:");
        return;
    }

    if ($step === 'await_password') {
        $username = $state['username'] ?? '';
        $user_id  = $state['user_id'] ?? 0;

        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name, role, password FROM users WHERE id=? AND username=?");
        $stmt->execute([$user_id, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($text, $user['password'])) {
            sendMsg($chat_id, "❌ <b>Parol noto'g'ri!</b>\n\nQayta urinib ko'ring yoki /link buyrug'i bilan qaytadan boshlang.");
            return;
        }

        $db->prepare("UPDATE users SET telegram_chat_id=? WHERE id=?")->execute([$chat_id, $user['id']]);
        clearBotState($chat_id);

        $roleNames = ['admin' => 'Admin 👑', 'teacher' => "O'qituvchi 👨‍🏫", 'student' => "O'quvchi 🎓", 'parent' => 'Ota-ona 👪'];
        $roleName  = $roleNames[$user['role']] ?? $user['role'];

        sendMsg($chat_id,
            "🎉 <b>Muvaffaqiyatli ulandi!</b>\n\n" .
            "👤 Ism: <b>{$user['full_name']}</b>\n" .
            "🏷️ Rol: <b>{$roleName}</b>\n\n" .
            "Endi Telegram orqali barcha bildirishnomalarni olasiz! ✅",
            mainKeyboard($chat_id)
        );

        echo "    ✅ Yangi ulandi: {$user['full_name']} ({$user['role']}) — chat_id={$chat_id}\n";
        return;
    }
}

// ============================================================
// MENYU FUNKSIYALARI
// ============================================================
function sendWelcome($chat_id, $fname) {
    $linked = isLinked($chat_id);
    $text   = "🏫 <b>ZiyoCRM</b> botiga xush kelibsiz, <b>{$fname}</b>!\n\n";

    if ($linked) {
        $text .= "✅ Akkauntingiz ulangan.\nQuyidagi menyudan foydalaning.";
        sendMsg($chat_id, $text, mainKeyboard($chat_id));
    } else {
        $text .= "Bu bot orqali siz:\n";
        $text .= "• ⚠️ Ogohlantirishlar haqida xabar olasiz\n";
        $text .= "• 📅 Davomat ma'lumotlarini ko'rasiz\n";
        $text .= "• 💰 To'lov holati haqida bildirishlar olasiz\n";
        $text .= "• 📢 Maktab e'lonlarini ko'rasiz\n\n";
        $text .= "Boshlash uchun akkauntingizni ulaing 👇";

        $kb = ['inline_keyboard' => [
            [['text' => '🔗 Akkauntni ulash', 'callback_data' => 'link_account']],
            [['text' => '🆔 Mening ID im',    'callback_data' => 'get_id']],
        ]];
        sendMsg($chat_id, $text, $kb);
    }
}

function sendHelp($chat_id) {
    $text = "📖 <b>ZiyoCRM Bot — Yordam</b>\n\n";
    $text .= "📌 <b>Buyruqlar:</b>\n";
    $text .= "/start — Bosh menyuga qaytish\n";
    $text .= "/link  — Akkauntni ulash\n";
    $text .= "/myid  — Telegram ID ni olish\n";
    $text .= "/help  — Yordam\n";
    $text .= "/cancel — Amalni bekor qilish\n\n";
    $text .= "❓ <b>Muammo bormi?</b>\n";
    $text .= "Admin bilan bog'laning yoki /myid orqali IDingizni olib adminга bering.";
    sendMsg($chat_id, $text);
}

function sendMyInfo($chat_id) {
    $user = getUserByChatId($chat_id);
    if (!$user) { sendNotLinked($chat_id); return; }
    $roleNames = ['admin' => 'Admin 👑', 'teacher' => "O'qituvchi 👨‍🏫", 'student' => "O'quvchi 🎓", 'parent' => 'Ota-ona 👪'];
    $roleName  = $roleNames[$user['role']] ?? $user['role'];
    $text = "👤 <b>Mening ma'lumotlarim</b>\n\n";
    $text .= "📛 Ism: <b>{$user['full_name']}</b>\n";
    $text .= "🔖 Login: <code>{$user['username']}</code>\n";
    $text .= "🏷️ Rol: <b>{$roleName}</b>\n";
    $text .= "📱 Telefon: " . ($user['phone'] ?: '—') . "\n";
    $text .= "🆔 Chat ID: <code>{$chat_id}</code>\n";
    $text .= "🕐 Ro'yxatdan o'tgan: " . substr($user['created_at'], 0, 10);
    sendMsg($chat_id, $text);
}

function sendMyWarnings($chat_id) {
    $user = getUserByChatId($chat_id);
    if (!$user) { sendNotLinked($chat_id); return; }
    $db = getDB();
    $student_id = $user['id'];
    if ($user['role'] === 'parent') {
        $r = $db->prepare("SELECT student_id FROM parent_student WHERE parent_id=? LIMIT 1");
        $r->execute([$user['id']]);
        $row = $r->fetch(PDO::FETCH_ASSOC);
        $student_id = $row['student_id'] ?? null;
        if (!$student_id) { sendMsg($chat_id, "ℹ️ Farzandingiz tizimga ulanmagan."); return; }
    }
    if (!in_array($user['role'], ['student', 'parent'])) {
        sendMsg($chat_id, "⛔ Bu funksiya faqat o'quvchi va ota-onalar uchun."); return;
    }
    $stmt = $db->prepare("SELECT w.*, u.full_name as teacher_name FROM warnings w JOIN users u ON w.teacher_id=u.id WHERE w.student_id=? ORDER BY w.created_at DESC LIMIT 10");
    $stmt->execute([$student_id]);
    $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$warnings) { sendMsg($chat_id, "✅ <b>Ogohlantirishlar yo'q!</b>\n\nHozircha hech qanday ogohlantirish qayd etilmagan. 🎉"); return; }
    $typeEmoji = ['xulq'=>'😠','kechikish'=>'⏰','dars'=>'📚','boshqa'=>'⚠️'];
    $text = "⚠️ <b>Ogohlantirishlar</b> (" . count($warnings) . " ta)\n\n";
    foreach ($warnings as $i => $w) {
        $emoji = $typeEmoji[$w['type']] ?? '⚠️';
        $text .= ($i+1) . ". {$emoji} <b>" . ucfirst($w['type']) . "</b>\n";
        $text .= "   📝 {$w['description']}\n";
        $text .= "   👨‍🏫 {$w['teacher_name']}\n";
        $text .= "   📅 " . substr($w['created_at'], 0, 10) . "\n\n";
    }
    sendMsg($chat_id, $text);
}

function sendMyAttendance($chat_id) {
    $user = getUserByChatId($chat_id);
    if (!$user) { sendNotLinked($chat_id); return; }
    $db = getDB();
    $student_id = $user['id'];
    if ($user['role'] === 'parent') {
        $r = $db->prepare("SELECT student_id FROM parent_student WHERE parent_id=? LIMIT 1");
        $r->execute([$user['id']]);
        $row = $r->fetch(PDO::FETCH_ASSOC);
        $student_id = $row['student_id'] ?? null;
        if (!$student_id) { sendMsg($chat_id, "ℹ️ Farzandingiz tizimga ulanmagan."); return; }
    }
    $stmt = $db->prepare("SELECT a.*, g.name as group_name FROM attendance a JOIN groups g ON a.group_id=g.id WHERE a.student_id=? ORDER BY a.date DESC LIMIT 14");
    $stmt->execute([$student_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { sendMsg($chat_id, "📅 Davomat ma'lumotlari yo'q."); return; }
    $statusEmoji = ['present'=>'✅','absent'=>'❌','late'=>'⏰'];
    $text = "📅 <b>Davomat (so'nggi 14 ta)</b>\n\n";
    foreach ($rows as $r) {
        $e = $statusEmoji[$r['status']] ?? '❓';
        $text .= "{$e} {$r['date']} — {$r['group_name']}\n";
    }
    sendMsg($chat_id, $text);
}

function sendMyPayment($chat_id) {
    $user = getUserByChatId($chat_id);
    if (!$user) { sendNotLinked($chat_id); return; }
    $db = getDB();
    $student_id = $user['id'];
    if ($user['role'] === 'parent') {
        $r = $db->prepare("SELECT student_id FROM parent_student WHERE parent_id=? LIMIT 1");
        $r->execute([$user['id']]);
        $row = $r->fetch(PDO::FETCH_ASSOC);
        $student_id = $row['student_id'] ?? null;
        if (!$student_id) { sendMsg($chat_id, "ℹ️ Farzandingiz tizimga ulanmagan."); return; }
    }
    $stmt = $db->prepare("SELECT * FROM payments WHERE student_id=? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$student_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { sendMsg($chat_id, "💰 To'lov ma'lumotlari yo'q."); return; }
    $statusEmoji = ['paid'=>'✅','pending'=>'⏳','overdue'=>'🔴'];
    $text = "💰 <b>To'lov holati</b>\n\n";
    foreach ($rows as $r) {
        $e = $statusEmoji[$r['status']] ?? '❓';
        $amount = number_format($r['amount'], 0, '.', ' ');
        $text .= "{$e} {$r['month']} — <b>{$amount} so'm</b>\n";
        if ($r['note']) $text .= "   📝 {$r['note']}\n";
    }
    sendMsg($chat_id, $text);
}

function sendMyLessons($chat_id) {
    $user = getUserByChatId($chat_id);
    if (!$user) { sendNotLinked($chat_id); return; }
    $db = getDB();
    if ($user['role'] === 'teacher') {
        $stmt = $db->prepare("SELECT name, subject FROM groups WHERE teacher_id=?");
        $stmt->execute([$user['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { sendMsg($chat_id, "📚 Siz hali hech qanday guruhga biriktirilmadingiz."); return; }
        $text = "📚 <b>Mening guruhlarim</b>\n\n";
        foreach ($rows as $r) $text .= "• <b>{$r['name']}</b> — {$r['subject']}\n";
    } else {
        $student_id = $user['id'];
        if ($user['role'] === 'parent') {
            $r = $db->prepare("SELECT student_id FROM parent_student WHERE parent_id=? LIMIT 1");
            $r->execute([$user['id']]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            $student_id = $row['student_id'] ?? null;
            if (!$student_id) { sendMsg($chat_id, "ℹ️ Farzandingiz tizimga ulanmagan."); return; }
        }
        $stmt = $db->prepare("SELECT g.name, g.subject, u.full_name as teacher FROM student_groups sg JOIN groups g ON sg.group_id=g.id LEFT JOIN users u ON g.teacher_id=u.id WHERE sg.student_id=?");
        $stmt->execute([$student_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { sendMsg($chat_id, "📚 Siz hali hech qanday guruhga qo'shilmadingiz."); return; }
        $text = "📚 <b>Mening darslarim</b>\n\n";
        foreach ($rows as $r) {
            $text .= "• <b>{$r['name']}</b> — {$r['subject']}\n";
            if ($r['teacher']) $text .= "  👨‍🏫 {$r['teacher']}\n";
        }
    }
    sendMsg($chat_id, $text);
}

function sendNotLinked($chat_id) {
    $kb = ['inline_keyboard' => [[['text' => '🔗 Akkauntni ulash', 'callback_data' => 'link_account']]]];
    sendMsg($chat_id, "⚠️ Akkauntingiz ulanmagan.\n/link buyrug'i bilan ulaing.", $kb);
}

// ============================================================
// YORDAMCHILAR
// ============================================================
function getUserByChatId($chat_id) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE telegram_chat_id=? AND is_active=1 LIMIT 1");
    $stmt->execute([(string)$chat_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function isLinked($chat_id) { return getUserByChatId($chat_id) !== null; }

function getBotState($chat_id) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT message FROM telegram_logs WHERE chat_id=? AND status='state' ORDER BY sent_at DESC LIMIT 1");
    $stmt->execute([(string)$chat_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? json_decode($row['message'], true) : null;
}

function setBotState($chat_id, $state) {
    $db = getDB();
    $db->prepare("DELETE FROM telegram_logs WHERE chat_id=? AND status='state'")->execute([(string)$chat_id]);
    $db->prepare("INSERT INTO telegram_logs (chat_id, message, status) VALUES (?, ?, 'state')")->execute([(string)$chat_id, json_encode($state)]);
}

function clearBotState($chat_id) {
    $db = getDB();
    $db->prepare("DELETE FROM telegram_logs WHERE chat_id=? AND status='state'")->execute([(string)$chat_id]);
}

function mainKeyboard($chat_id = null) {
    if ($chat_id) {
        $user = getUserByChatId($chat_id);
        if ($user) {
            if ($user['role'] === 'teacher') {
                return ['keyboard' => [["📊 Mening ma'lumotlarim","📚 Darslar"],["📅 Davomat","🆔 ID olish"]], 'resize_keyboard' => true];
            }
            if ($user['role'] === 'admin') {
                return ['keyboard' => [["📊 Mening ma'lumotlarim","🆔 ID olish"]], 'resize_keyboard' => true];
            }
        }
    }
    return ['keyboard' => [
        ["📊 Mening ma'lumotlarim","⚠️ Ogohlantirishlar"],
        ["📅 Davomat","💰 To'lov"],
        ["📚 Darslar","🆔 ID olish"],
    ], 'resize_keyboard' => true];
}

function sendMsg($chat_id, $text, $keyboard = null) {
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    $url  = TELEGRAM_API_URL . 'sendMessage';
    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query($data), 'timeout' => 10]];
    $res  = @file_get_contents($url, false, stream_context_create($opts));
    if ($res === false) echo "    ⚠️  Xabar yuborilmadi!\n";
    return $res;
}

function answerCb($callback_id) {
    $url  = TELEGRAM_API_URL . 'answerCallbackQuery';
    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query(['callback_query_id' => $callback_id]), 'timeout' => 5]];
    @file_get_contents($url, false, stream_context_create($opts));
}

// ============================================================
// QR LOGIN ISHLOVCHILARI
// ============================================================
function handleQrLoginStart($chat_id, $token, $fname) {
    $user = getUserByChatId($chat_id);
    if (!$user) {
        sendMsg($chat_id, "⚠️ <b>Hisob belgilanmagan!</b>\n\nTizimga QR kod orqali kirish uchun avval Telegram akkauntingizni ulab oling.\n\nAkkauntni ulash uchun <code>/link</code> buyrug'ini yuboring.", [
            'inline_keyboard' => [[['text' => '🔗 Akkauntni ulash', 'callback_data' => 'link_account']]]
        ]);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM qr_logins WHERE token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$qr || strtotime($qr['expires_at']) < time()) {
        sendMsg($chat_id, "❌ <b>QR Kod muddati o'tgan yoki yaroqsiz!</b>\n\nIltimos, saytdan yangi QR kod olib qayta skanerlang.");
        return;
    }

    $roleNames = ['admin' => 'Admin 👑', 'teacher' => "O'qituvchi 👨‍🏫", 'student' => "O'quvchi 🎓", 'parent' => 'Ota-ona 👪'];
    $roleName  = $roleNames[$user['role']] ?? $user['role'];

    $text = "🔐 <b>ZiyoCRM — Kirishni tasdiqlash</b>\n\n" .
            "👤 Akkaunt: <b>{$user['full_name']}</b> ({$roleName})\n" .
            "🖥️ Qurilma saytda kirishni so'ramoqda.\n\n" .
            "Tizimga kirishga ruxsat berasizmi?";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '✅ HA, Ruxsat berish', 'callback_data' => 'qr_approve_' . $token],
                ['text' => '❌ YO\'Q, Bekor qilish', 'callback_data' => 'qr_reject_' . $token]
            ]
        ]
    ];

    sendMsg($chat_id, $text, $keyboard);
}

function handleQrLoginResponse($chat_id, $token, $isApproved) {
    $user = getUserByChatId($chat_id);
    if (!$user) {
        sendMsg($chat_id, "❌ Xatolik: Foydalanuvchi topilmadi.");
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM qr_logins WHERE token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$qr || strtotime($qr['expires_at']) < time()) {
        sendMsg($chat_id, "⌛ QR kod muddati tugagan.");
        return;
    }

    if ($isApproved) {
        $db->prepare("UPDATE qr_logins SET status = 'approved', chat_id = ?, user_id = ? WHERE id = ?")
           ->execute([(string)$chat_id, $user['id'], $qr['id']]);
        sendMsg($chat_id, "✅ <b>Kirish tasdiqlandi!</b>\n\nSayt avtomatik ravishda shaxsiy kabinetingizga yo'naltirildi.");
    } else {
        $db->prepare("UPDATE qr_logins SET status = 'rejected' WHERE id = ?")
           ->execute([$qr['id']]);
        sendMsg($chat_id, "🚫 <b>Kirish bekor qilindi.</b>");
    }
}

