<?php
// ==============================================
// ZiyoCRM - Teacher Test Generator & Importer
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('teacher');

$db = getDB();
$user = getCurrentUser();
$msg = $err = '';

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
<title>O'qituvchi — AI & Fayldan Test Yuklash — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; }
.table th { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
</style>
</head>
<body class="role-teacher">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🤖 AI Test Yaratish & Fayldan Test Yuklash</div>
            <div class="topbar-right">
                <span class="badge badge-primary" style="font-size:13px; padding:6px 12px;">👨‍🏫 O'qituvchi Paneli</span>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Grid 2: AI & File -->
            <div class="grid-2" style="margin-bottom: 28px; gap: 24px;">

                <!-- AI Generator -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ AI Bilan Test Yaratish</h3>
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
                                <label>Nechta Level Yaratilsin?</label>
                                <input type="number" name="num_levels" value="3" min="1" max="10" required style="padding-left:14px;">
                            </div>
                            <div class="form-group">
                                <label>Savollar Soni (Har birida)</label>
                                <input type="number" value="20" disabled style="padding-left:14px; opacity:0.7;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Mavzu (Ixtiyoriy)</label>
                            <input type="text" name="topic" placeholder="Masalan: Trigonometriya formulalari" style="padding-left:14px;">
                        </div>

                        <button type="submit" id="btnGen" class="btn btn-teacher btn-lg" style="width:100%;">
                            ⚡ AI Orqali Test Yaratish
                        </button>
                    </form>
                    <div id="aiStatus" style="margin-top:14px;"></div>
                </div>

                <!-- File Importer -->
                <div class="card">
                    <div class="card-header">
                        <h3>📁 Fayldan Test Yuklash</h3>
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
                            <label>Test Sarlavhasi</label>
                            <input type="text" name="test_title" placeholder="Masalan: 3-Guruh Nazorat Testi" required style="padding-left:14px;">
                        </div>

                        <div class="form-group">
                            <label>Test Fayli (.txt, .json, .csv)</label>
                            <div class="file-dropzone" onclick="document.getElementById('testFileInput').click();">
                                <div style="font-size:28px; margin-bottom:6px;">📄</div>
                                <div style="font-weight:700; font-size:14px;" id="fileNameDisplay">Faylni yuklash uchun bosing</div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Format: Savol | OptionA | OptionB | OptionC | OptionD | Correct(A/B/C/D)</div>
                                <input type="file" id="testFileInput" name="test_file" accept=".txt,.json,.csv,.doc,.docx" required style="display:none;" onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name;">
                            </div>
                        </div>

                        <button type="submit" id="btnFileUpload" class="btn btn-success btn-lg" style="width:100%;">
                            📥 Faylni O'qish va Testni Saqlash
                        </button>
                    </form>
                    <div id="fileStatus" style="margin-top:14px;"></div>
                </div>

            </div>

            <!-- List of Levels -->
            <div class="card">
                <div class="card-header">
                    <h3>📚 Test Darajalari va To'plamlari (<?= count($levels) ?>)</h3>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Fan</th>
                                <th>Test Nomi</th>
                                <th>Savollar</th>
                                <th>Mukofot (Coins)</th>
                                <th>Amal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($levels as $lvl): ?>
                                <tr>
                                    <td>#<?= $lvl['id'] ?></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($lvl['subject_name']) ?></span></td>
                                    <td style="font-weight:700; color:#93c5fd;"><?= htmlspecialchars($lvl['title']) ?></td>
                                    <td><span class="badge badge-success"><?= $lvl['question_count'] ?> ta savol</span></td>
                                    <td><span class="yandex-price-pill" style="font-size:11px; padding:2px 8px;">🪙 +<?= $lvl['reward_coins'] ?> Tanga</span></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Ushbu testni o\'chirmoqchimisiz?');">
                                            <input type="hidden" name="level_id" value="<?= $lvl['id'] ?>">
                                            <button type="submit" name="delete_level" class="btn btn-sm btn-danger" style="padding:4px 8px; font-size:11px;">🗑️ O'chirish</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('aiGenForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btnGen');
    var status = document.getElementById('aiStatus');
    btn.disabled = true;
    btn.innerHTML = '⌛ Test Yaratilmoqda...';

    var formData = new FormData(this);
    formData.append('action', 'generate_levels');

    fetch('../api/ai_test_generator.php', { method: 'POST', body: formData })
    .then(res => res.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '⚡ AI Orqali Test Yaratish';
        if (data.success) {
            status.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            status.innerHTML = '<div class="alert alert-danger">Xatolik: ' + data.message + '</div>';
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '⚡ AI Orqali Test Yaratish';
        status.innerHTML = '<div class="alert alert-danger">Xatolik yuz berdi.</div>';
    });
});

document.getElementById('fileUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btnFileUpload');
    var status = document.getElementById('fileStatus');
    btn.disabled = true;
    btn.innerHTML = '⌛ Fayl Yuklanmoqda...';

    var formData = new FormData(this);
    formData.append('action', 'upload_test_file');

    fetch('../api/ai_test_generator.php', { method: 'POST', body: formData })
    .then(res => res.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '📥 Faylni O\'qish va Testni Saqlash';
        if (data.success) {
            status.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            status.innerHTML = '<div class="alert alert-danger">Xatolik: ' + data.message + '</div>';
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '📥 Faylni O\'qish va Testni Saqlash';
        status.innerHTML = '<div class="alert alert-danger">Faylni yuklashda xatolik.</div>';
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
