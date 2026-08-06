<?php
// ==============================================
// ZiyoCRM - Student Tests & Level Challenge
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('student');

$db = getDB();
$student = getCurrentUser();
$student_id = $student['id'];

// Get coin balance
$coinRow = $db->query("SELECT coins_balance FROM student_coins WHERE student_id = $student_id")->fetch();
$student_coins = $coinRow ? (int)$coinRow['coins_balance'] : 0;

$activeLevel = null;
$questions = [];
$scoreMsg = null;

// Selected Level
if (isset($_GET['level_id'])) {
    $level_id = (int)$_GET['level_id'];
    $stmtL = $db->prepare("SELECT l.*, s.name as subject_name FROM test_levels l JOIN subjects s ON l.subject_id=s.id WHERE l.id=?");
    $stmtL->execute([$level_id]);
    $activeLevel = $stmtL->fetch(PDO::FETCH_ASSOC);

    if ($activeLevel) {
        $stmtQ = $db->prepare("SELECT * FROM test_questions WHERE level_id=? ORDER BY id ASC");
        $stmtQ->execute([$level_id]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Submit Test Answers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $level_id = (int)$_POST['level_id'];
    $answers = $_POST['answers'] ?? [];

    $stmtQ = $db->prepare("SELECT id, correct_option FROM test_questions WHERE level_id=?");
    $stmtQ->execute([$level_id]);
    $allQ = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    $correctCount = 0;
    $totalCount = count($allQ);

    foreach ($allQ as $q) {
        if (isset($answers[$q['id']]) && strtoupper($answers[$q['id']]) === strtoupper($q['correct_option'])) {
            $correctCount++;
        }
    }

    $scorePercent = $totalCount > 0 ? round(($correctCount / $totalCount) * 100) : 0;
    $passed = $scorePercent >= 80 ? 1 : 0;
    $awardedCoins = $passed ? 5 : 0;

    // Record progress
    $db->prepare("INSERT INTO student_level_progress (student_id, level_id, score_percent, passed, coins_awarded) VALUES (?, ?, ?, ?, ?)")
       ->execute([$student_id, $level_id, $scorePercent, $passed, $awardedCoins]);

    if ($passed && $awardedCoins > 0) {
        // Award Coins
        $db->exec("INSERT INTO student_coins (student_id, coins_balance, total_earned) VALUES ($student_id, $awardedCoins, $awardedCoins)
            ON CONFLICT(student_id) DO UPDATE SET coins_balance = coins_balance + $awardedCoins, total_earned = total_earned + $awardedCoins");
        $student_coins += $awardedCoins;
    }

    $scoreMsg = [
        'percent' => $scorePercent,
        'passed' => $passed,
        'coins' => $awardedCoins,
        'correct' => $correctCount,
        'total' => $totalCount
    ];
}

// Subjects & Levels List
$subjects = $db->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$allLevels = $db->query("SELECT l.*, s.name as subject_name, 
    (SELECT COUNT(*) FROM test_questions q WHERE q.level_id=l.id) as question_count,
    (SELECT MAX(score_percent) FROM student_level_progress p WHERE p.level_id=l.id AND p.student_id=$student_id) as best_score
    FROM test_levels l JOIN subjects s ON l.subject_id=s.id ORDER BY s.name ASC, l.level_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Testlar & Coins Yaratish — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.test-card {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.25s ease;
}
.test-card:hover {
    border-color: rgba(99,102,241,0.4);
    transform: translateY(-3px);
}
</style>
</head>
<body class="role-student">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">📝 Test Yechish va Tanga Yig'ish</div>
            <div class="topbar-right">
                <div class="coin-balance-card" style="padding:6px 16px; border-radius:12px;">
                    <span>🪙 Balansingiz:</span>
                    <span class="coin-val" style="font-size:16px;"><?= number_format($student_coins) ?> Tanga</span>
                </div>
            </div>
        </div>

        <div class="page-content">

            <!-- Result Alert -->
            <?php if ($scoreMsg): ?>
                <div class="alert <?= $scoreMsg['passed'] ? 'alert-success' : 'alert-warning' ?>" style="font-size:15px; padding:20px; border-radius:16px; margin-bottom:24px;">
                    <h3 style="margin-bottom:8px; font-weight:800;">
                        <?= $scoreMsg['passed'] ? '🎉 Tabriklaymiz! Test Muvaffaqiyatli Topshirildi!' : '😔 Natija Yetarli Bo\'lmadi' ?>
                    </h3>
                    <p style="margin-bottom:10px;">
                        Siz <strong><?= $scoreMsg['total'] ?></strong> ta savoldan <strong><?= $scoreMsg['correct'] ?></strong> tasiga to'g'ri javob berdingiz (<strong><?= $scoreMsg['percent'] ?>%</strong>).
                    </p>
                    <?php if ($scoreMsg['passed']): ?>
                        <div style="font-weight:900; color:#fcd34d; font-size:16px;">🪙 +<?= $scoreMsg['coins'] ?> Tanga hisobingizga qo'shildi!</div>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--text-muted);">Tanga (coins) mukofotini olish uchun kamida 80% natija ko'rsatishingiz kerak.</div>
                    <?php endif; ?>
                    <div style="margin-top:16px;">
                        <a href="tests.php" class="btn btn-outline btn-sm">🔙 Testlar Ro'yxatiga Qaytish</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active Test Solver UI -->
            <?php if ($activeLevel && !empty($questions) && !$scoreMsg): ?>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <div>
                            <span class="badge badge-purple"><?= htmlspecialchars($activeLevel['subject_name']) ?></span>
                            <h2 style="font-size:20px; font-weight:800; margin-top:4px;"><?= htmlspecialchars($activeLevel['title']) ?></h2>
                        </div>
                        <a href="tests.php" class="btn btn-outline btn-sm">❌ Chiqish</a>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="level_id" value="<?= $activeLevel['id'] ?>">

                        <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:24px;">
                            <?php foreach ($questions as $idx => $q): ?>
                                <div style="background:var(--dark4); border:1px solid var(--border); border-radius:14px; padding:20px;">
                                    <div style="font-size:15px; font-weight:700; color:#fff; margin-bottom:16px;">
                                        <?= ($idx + 1) ?>. <?= htmlspecialchars($q['question_text']) ?>
                                    </div>

                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                        <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $optKey => $optVal): ?>
                                            <label class="question-option-label" id="lbl_<?= $q['id'] ?>_<?= $optKey ?>">
                                                <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $optKey ?>" required onchange="selectOption(<?= $q['id'] ?>, '<?= $optKey ?>')">
                                                <span class="opt-letter"><?= $optKey ?></span>
                                                <span><?= htmlspecialchars($optVal) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="submit_test" class="btn btn-primary btn-lg" style="width:100%;">
                            🚀 Testni Yakunlash va Natijani Ko'rish
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Available Tests Selection Grid -->
            <?php if (!$activeLevel || empty($questions)): ?>
                <div style="margin-bottom:20px;">
                    <h2 style="font-size:20px; font-weight:800; margin-bottom:4px;">🎯 Mavjud Testlar va Level Challenge</h2>
                    <p style="font-size:13px; color:var(--text-muted); margin:0;">O'zingiz xohlagan fan va darajadagi testni tanlab ishlang!</p>
                </div>

                <div class="grid-3" style="gap:20px;">
                    <?php foreach ($allLevels as $lvl): ?>
                        <div class="test-card">
                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                    <span class="badge badge-purple"><?= htmlspecialchars($lvl['subject_name']) ?></span>
                                    <span class="yandex-price-pill" style="font-size:11px; padding:2px 8px;">🪙 +<?= $lvl['reward_coins'] ?> Tanga</span>
                                </div>
                                <h3 style="font-size:16px; font-weight:700; margin-bottom:6px; color:#fff;"><?= htmlspecialchars($lvl['title']) ?></h3>
                                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;"><?= htmlspecialchars($lvl['description'] ?: '20 ta sinov savollari to\'plami') ?></p>
                            </div>

                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12px; margin-bottom:14px;">
                                    <span style="color:var(--text-muted);">❓ <?= $lvl['question_count'] ?> ta savol</span>
                                    <span>
                                        <?php if ($lvl['best_score'] !== null): ?>
                                            <span class="badge <?= $lvl['best_score'] >= 80 ? 'badge-success' : 'badge-warning' ?>">Best: <?= $lvl['best_score'] ?>%</span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">Topshirilmagan</span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <a href="tests.php?level_id=<?= $lvl['id'] ?>" class="btn btn-primary btn-sm" style="width:100%; text-decoration:none;">
                                    ✍️ Testni Tanlab Ishlash
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($allLevels)): ?>
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <div class="es-icon">📝</div>
                            <h4>Testlar hozircha mavjud emas</h4>
                            <p>Tez orada o'qituvchi va admin yangi test to'plamlarini joylaydi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function selectOption(qId, optKey) {
    ['A','B','C','D'].forEach(k => {
        const el = document.getElementById(`lbl_${qId}_${k}`);
        if (el) el.classList.remove('selected');
    });
    const target = document.getElementById(`lbl_${qId}_${optKey}`);
    if (target) target.classList.add('selected');
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const target = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', target);
    localStorage.setItem('theme', target);
}
</script>
</body>
</html>
