<?php
// ==============================================
// ZiyoCRM - Target CRM Lead Automation API
// ==============================================
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Meta / Instagram Lead Webhook / Direct Post
if ($action === 'create_lead') {
    $full_name = trim($_POST['full_name'] ?? $_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = trim($_POST['source'] ?? 'Instagram');
    $subject_interest = trim($_POST['subject_interest'] ?? $_POST['course'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($full_name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Ism va telefon raqami kiritilishi shart!']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO leads (full_name, phone, source, subject_interest, stage, notes) VALUES (?, ?, ?, ?, 'new', ?)");
    $stmt->execute([$full_name, $phone, $source, $subject_interest, $notes]);
    $lead_id = $db->lastInsertId();

    // Adminlarga Telegram orqali tezyordam bildirishnomasi
    $msg = "🎯 <b>YANGI TARGET LID KELDI!</b>\n\n";
    $msg .= "👤 <b>Ismi:</b> " . htmlspecialchars($full_name) . "\n";
    $msg .= "📞 <b>Tel:</b> " . htmlspecialchars($phone) . "\n";
    $msg .= "📌 <b>Manba:</b> " . htmlspecialchars($source) . "\n";
    $msg .= "📚 <b>Qiziqqan kursi:</b> " . htmlspecialchars($subject_interest) . "\n\n";
    $msg .= "⚡ <i>Darhol bo'g'laning va sotuvni amalga oshiring!</i>";

    // Adminga telegram xabar yuborish
    $admins = $db->query("SELECT telegram_chat_id FROM users WHERE role='admin' AND telegram_chat_id IS NOT NULL")->fetchAll();
    foreach ($admins as $adm) {
        sendTelegram($adm['telegram_chat_id'], $msg);
    }

    echo json_encode(['success' => true, 'message' => 'Lid muvaffaqiyatli qabul qilindi!', 'lead_id' => $lead_id]);
    exit;
}

// 2. Stage almashtirish (Kanban board drag & drop)
if ($action === 'update_stage') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Ruxsat yo`q!']);
        exit;
    }

    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $stage = trim($_POST['stage'] ?? '');

    $validStages = ['new', 'contacted', 'trial_scheduled', 'enrolled', 'lost'];
    if (!$lead_id || !in_array($stage, $validStages)) {
        echo json_encode(['success' => false, 'message' => 'Yaroqsiz ma`lumot!']);
        exit;
    }

    $stmt = $db->prepare("UPDATE leads SET stage = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$stage, $lead_id]);

    echo json_encode(['success' => true, 'message' => 'Bosqich yangilandi!']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Noma`lum amaliyot!']);
