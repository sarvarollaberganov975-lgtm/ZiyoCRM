<?php
// ==============================================
// ZiyoCRM - Target CRM (Lead Sales Funnel & Kanban)
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('admin');

$db = getDB();
$user = getCurrentUser();
$msg = '';

// Add Lead
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lead'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = trim($_POST['source'] ?? 'Instagram');
    $subject_interest = trim($_POST['subject_interest'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($full_name) && !empty($phone)) {
        $stmt = $db->prepare("INSERT INTO leads (full_name, phone, source, subject_interest, stage, notes) VALUES (?, ?, ?, ?, 'new', ?)");
        $stmt->execute([$full_name, $phone, $source, $subject_interest, $notes]);
        $msg = "✅ Yangi lid ro'yxatga olindi!";
    }
}

// Update Lead Stage via AJAX / Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stage'])) {
    $lead_id = (int)$_POST['lead_id'];
    $new_stage = $_POST['new_stage'];
    $db->prepare("UPDATE leads SET stage=? WHERE id=?")->execute([$new_stage, $lead_id]);
    $msg = "✅ Lid bosqichi yangilandi!";
}

// Fetch all leads
$leads = $db->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$stages = [
    'new' => ['title' => '📥 Yangi Lidlar', 'color' => '#3b82f6'],
    'contacted' => ['title' => '📞 Bog`lanildi', 'color' => '#f59e0b'],
    'trial_scheduled' => ['title' => '🗓️ Sinov Darsi', 'color' => '#8b5cf6'],
    'enrolled' => ['title' => '🎉 A`zo Bo`ldi', 'color' => '#10b981'],
    'lost' => ['title' => '❌ Rad Etildi', 'color' => '#ef4444']
];

$categorized = [
    'new' => [],
    'contacted' => [],
    'trial_scheduled' => [],
    'enrolled' => [],
    'lost' => []
];

foreach ($leads as $lead) {
    $st = $lead['stage'] ?? 'new';
    if (isset($categorized[$st])) {
        $categorized[$st][] = $lead;
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Target CRM & Lead Funnel — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.kanban-board {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 20px;
}
.kanban-col {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px;
    min-width: 220px;
    display: flex;
    flex-direction: column;
}
.kanban-header {
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}
.lead-card {
    background: var(--dark4);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
}
.lead-card:hover {
    border-color: rgba(99,102,241,0.4);
    transform: translateY(-2px);
}
@media(max-width: 1024px) {
    .kanban-board { grid-template-columns: repeat(5, 260px); }
}
</style>
</head>
<body class="role-admin">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🎯 Target CRM & Lid Boshqaruvi</div>
            <div class="topbar-right">
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('addLeadModal').classList.add('show')">
                    ➕ Yangi Lid Qo'shish
                </button>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Header Info -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:20px; font-weight:800;">📊 Sotuv Voronkasi (Kanban Board)</h2>
                    <p style="font-size:12px; color:var(--text-muted); margin:0;">Mijozlarni sotuv bosqichlari bo'yicha kuzatish va boshqarish</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <span class="badge badge-purple">Jami Lidlar: <?= count($leads) ?> ta</span>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="kanban-board">
                <?php foreach ($stages as $key => $stInfo): ?>
                    <div class="kanban-col">
                        <div class="kanban-header" style="color: <?= $stInfo['color'] ?>;">
                            <span><?= $stInfo['title'] ?></span>
                            <span class="badge badge-gray"><?= count($categorized[$key]) ?></span>
                        </div>

                        <div style="flex:1;">
                            <?php foreach ($categorized[$key] as $ld): ?>
                                <div class="lead-card">
                                    <div style="font-weight:700; font-size:14px; color:#fff; margin-bottom:4px;"><?= htmlspecialchars($ld['full_name']) ?></div>
                                    <div style="font-size:12px; color:#93c5fd; margin-bottom:6px;">📞 <?= htmlspecialchars($ld['phone']) ?></div>
                                    
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; margin-bottom:8px;">
                                        <span class="badge badge-gray">🌐 <?= htmlspecialchars($ld['source']) ?></span>
                                        <span style="color:var(--text-muted);"><?= htmlspecialchars($ld['subject_interest'] ?: '—') ?></span>
                                    </div>

                                    <?php if (!empty($ld['notes'])): ?>
                                        <div style="font-size:11px; color:var(--text-muted); background:rgba(255,255,255,0.03); padding:6px; border-radius:6px; margin-bottom:8px;">
                                            <?= htmlspecialchars($ld['notes']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quick Stage Move -->
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="lead_id" value="<?= $ld['id'] ?>">
                                        <input type="hidden" name="update_stage" value="1">
                                        <select name="new_stage" onchange="this.form.submit()" style="font-size:11px; padding:4px 8px; border-radius:6px; width:100%;">
                                            <?php foreach ($stages as $sk => $sv): ?>
                                                <option value="<?= $sk ?>" <?= $ld['stage'] === $sk ? 'selected' : '' ?>><?= $sv['title'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($categorized[$key])): ?>
                                <div style="text-align:center; color:var(--text-muted); font-size:12px; padding:20px 0;">Bo'sh</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Yangi Lid Qo'shish -->
<div class="modal-overlay" id="addLeadModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🎯 Yangi Lid Qo'shish</h3>
            <button class="modal-close" onclick="document.getElementById('addLeadModal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_lead" value="1">

            <div class="form-group">
                <label>Foydalanuvchi / Mijoz Nomi</label>
                <input type="text" name="full_name" placeholder="Masalan: Alisher Vohidov" required style="padding-left:14px;">
            </div>

            <div class="form-group">
                <label>Telefon Raqami</label>
                <input type="text" name="phone" placeholder="+998 90 123 45 67" required style="padding-left:14px;">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Manbaa (Manba)</label>
                    <select name="source" style="padding-left:14px;">
                        <option value="Instagram">Instagram</option>
                        <option value="Telegram">Telegram</option>
                        <option value="Facebook">Facebook</option>
                        <option value="Website">Veb-sayt</option>
                        <option value="Other">Boshqa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Qiziqqan Fani</label>
                    <input type="text" name="subject_interest" placeholder="Ingliz tili, Matematika..." style="padding-left:14px;">
                </div>
            </div>

            <div class="form-group">
                <label>Qo'shimcha Izoh</label>
                <textarea name="notes" placeholder="Mijoz bilan suhbat haqida..." style="padding-left:14px; min-height:70px;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:10px;">💾 Lidni Saqlash</button>
        </form>
    </div>
</div>

<script>
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const target = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', target);
    localStorage.setItem('theme', target);
}
</script>
</body>
</html>
