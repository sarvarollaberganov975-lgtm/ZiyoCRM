<?php
// ==============================================
// ZiyoCRM - AI Test Generator & File Upload
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('admin');

$db = getDB();
$user = getCurrentUser();
$msg = $err = '';

// Delete level
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_level'])) {
    $level_id = (int)$_POST['level_id'];
    $db->prepare("DELETE FROM test_questions WHERE level_id=?")->execute([$level_id]);
    $db->prepare("DELETE FROM test_levels WHERE id=?")->execute([$level_id]);
    $msg = "🗑️ Test darajasi o'chirildi!";
}

$subjects = $db->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$levels = $db->query("SELECT l.*, s.name as subject_name, (SELECT COUNT(*) FROM test_questions q WHERE q.level_id=l.id) as question_count FROM test_levels l JOIN subjects s ON l.subject_id=s.id ORDER BY s.name ASC, l.level_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Test Yaratuvchi & Darajalar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; }
.table th { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
</style>
</head>
<body class="role-admin">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🤖 AI Test Yaratuvchi & Test Yuklash</div>
            <div class="topbar-right">
                <span class="badge badge-purple" style="font-size:13px; padding:6px 12px;">💡 AI & File Importer</span>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Grid 2: AI Generator & File Importer -->
            <div class="grid-2" style="margin-bottom: 28px; gap: 24px;">

                <!-- 1. AI Generator Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>🤖 AI Bilan Avtomatik Test Yaratish</h3>
                    </div>
                    <form id="aiGenForm">
                        <div class="form-group">
                            <label>Fanni Tanlang</label>
                            <select name="subject_id" required>
                                <option value="">-- Fan tanlang --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Level'lar Soni</label>
                                <input type="number" name="num_levels" value="5" min="1" max="20" required style="padding-left:14px;">
                            </div>
                            <div class="form-group">
                                <label>Savollar Soni (Har Level)</label>
                                <input type="number" value="20" disabled style="padding-left:14px; opacity:0.7;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Mavzu Yoki Maxsus Topshiriq (Ixtiyoriy)</label>
                            <input type="text" name="topic" placeholder="Masalan: Present Simple, Trigonometriya..." style="padding-left:14px;">
                        </div>

                        <button type="submit" id="btnGen" class="btn btn-primary btn-lg" style="width:100%;">
                            ⚡ AI Yordamida Test Generatsiya Qilish
                        </button>
                    </form>
                    <div id="aiStatus" style="margin-top:14px;"></div>
                </div>

                <!-- 2. File Upload Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>📁 Fayldan Test Yuklash (TXT, JSON, CSV)</h3>
                    </div>
                    <form id="fileUploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Fanni Tanlang</label>
                            <select name="subject_id" required>
                                <option value="">-- Fan tanlang --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Test Nomi / Sarlavhasi</label>
                            <input type="text" name="test_title" placeholder="Masalan: 1-Chorak Yakuniy Sinov Testi" required style="padding-left:14px;">
                        </div>

                        <div class="form-group">
                            <label>Test Fayli (.txt, .json, .csv)</label>
                            <div class="file-dropzone" onclick="document.getElementById('testFileInput').click();">
                                <div style="font-size:28px; margin-bottom:6px;">📄</div>
                                <div style="font-weight:700; font-size:14px;" id="fileNameDisplay">Faylni tanlash uchun bosing</div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Format: Savol | VariantA | VariantB | VariantC | VariantD | To'g'ri(A/B/C/D)</div>
                                <input type="file" id="testFileInput" name="test_file" accept=".txt,.json,.csv,.doc,.docx" required style="display:none;" onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name;">
                            </div>
                        </div>

                        <button type="submit" id="btnFileUpload" class="btn btn-success btn-lg" style="width:100%;">
                            📥 Fayldan Test Yuklash va Saqlash
                        </button>
                    </form>
                    <div id="fileStatus" style="margin-top:14px;"></div>
                </div>

            </div>

            <!-- Table of Created Levels -->
            <div class="card">
                <div class="card-header">
                    <h3>📚 Mavjud Test Darajalari va To'plamlari (<?= count($levels) ?>)</h3>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Fan</th>
                                <th>Test Darajasi / Sarlavha</th>
                                <th>Savollar</th>
                                <th>Mukofot (Coins)</th>
                                <th>O'tish Balli</th>
                                <th>Amal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($levels as $lvl): ?>
                                <tr>
                                    <td>#<?= $lvl['id'] ?></td>
                                    <td><span class="badge badge-purple"><?= htmlspecialchars($lvl['subject_name']) ?></span></td>
                                    <td style="font-weight:700; color:#c4b5fd;"><?= htmlspecialchars($lvl['title']) ?></td>
                                    <td><span class="badge badge-success"><?= $lvl['question_count'] ?> ta savol</span></td>
                                    <td><span class="yandex-price-pill" style="font-size:11px; padding:2px 8px;">🪙 +<?= $lvl['reward_coins'] ?> Tanga</span></td>
                                    <td><?= $lvl['passing_score'] ?>%</td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Ushbu test darajasini va barcha savollarni o\'chirmoqchimisiz?');">
                                            <input type="hidden" name="level_id" value="<?= $lvl['id'] ?>">
                                            <button type="submit" name="delete_level" class="btn btn-sm btn-danger" style="padding:4px 8px; font-size:11px;">🗑️ O'chirish</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($levels)): ?>
                                <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:20px;">Hozircha testlar yaratilmagan. Yuqoridagi formadan AI yoki Fayl orqali yaratishingiz mumkin.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// AI Generation Form Submit
document.getElementById('aiGenForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btnGen');
    var status = document.getElementById('aiStatus');
    
    btn.disabled = true;
    btn.innerHTML = '⌛ AI Test Yaratmoqda...';
    status.innerHTML = '<div class="alert alert-info">Sun\'iy intellekt har bir level uchun savollar generatsiya qilmoqda, kuting...</div>';

    var formData = new FormData(this);
    formData.append('action', 'generate_levels');

    fetch('../api/ai_test_generator.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '⚡ AI Yordamida Test Generatsiya Qilish';
        if (data.success) {
            status.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            status.innerHTML = '<div class="alert alert-danger">Xatolik: ' + data.message + '</div>';
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '⚡ AI Yordamida Test Generatsiya Qilish';
        status.innerHTML = '<div class="alert alert-danger">Tizimda xatolik yuz berdi.</div>';
    });
});

// File Upload Form Submit
document.getElementById('fileUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btnFileUpload');
    var status = document.getElementById('fileStatus');
    
    btn.disabled = true;
    btn.innerHTML = '⌛ Fayl Ishlanmoqda...';

    var formData = new FormData(this);
    formData.append('action', 'upload_test_file');

    fetch('../api/ai_test_generator.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '📥 Fayldan Test Yuklash va Saqlash';
        if (data.success) {
            status.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            status.innerHTML = '<div class="alert alert-danger">Xatolik: ' + data.message + '</div>';
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '📥 Fayldan Test Yuklash va Saqlash';
        status.innerHTML = '<div class="alert alert-danger">Faylni o\'qishda xatolik yuz berdi.</div>';
    });
});

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const target = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', target);
    localStorage.setItem('theme', target);
}
</script>
</body>
</html>
