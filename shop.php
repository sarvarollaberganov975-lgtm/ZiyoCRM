<?php
// ==============================================
// ZiyoCRM - Yandex Go Style Storefront for Students
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('student');

$db = getDB();
$student = getCurrentUser();
$student_id = $student['id'];

// Check and add category column if missing
try {
    $db->exec("ALTER TABLE shop_items ADD COLUMN category TEXT DEFAULT 'Sovg''alar'");
} catch (Exception $e) {
    // Column already exists
}

// Get student coins balance
$coinRow = $db->query("SELECT coins_balance FROM student_coins WHERE student_id = $student_id")->fetch();
$student_coins = $coinRow ? (int)$coinRow['coins_balance'] : 0;

$msg = null;

// Handle Purchase Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_item'])) {
    $item_id = (int)$_POST['item_id'];
    
    $stmtI = $db->prepare("SELECT * FROM shop_items WHERE id=? AND is_active=1");
    $stmtI->execute([$item_id]);
    $item = $stmtI->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        if ($item['stock_quantity'] <= 0) {
            $msg = ['type' => 'danger', 'text' => "Kechirasiz, ushbu sovg'adan omborda qolmagan!"];
        } elseif ($student_coins >= $item['coin_price']) {
            // Deduct coins & reduce stock
            $db->exec("UPDATE student_coins SET coins_balance = coins_balance - {$item['coin_price']} WHERE student_id = $student_id");
            $db->exec("UPDATE shop_items SET stock_quantity = stock_quantity - 1 WHERE id = $item_id");
            
            // Insert into gift redemptions
            $stmtR = $db->prepare("INSERT INTO gift_redemptions (student_id, item_id, coins_spent, status) VALUES (?, ?, ?, 'pending')");
            $stmtR->execute([$student_id, $item_id, $item['coin_price']]);

            $msg = ['type' => 'success', 'text' => "🎉 Tabriklaymiz! '{$item['name']}' uchun so'rovingiz qabul qilindi. Admin tez orada javob beradi!"];
            $student_coins -= $item['coin_price'];
        } else {
            $msg = ['type' => 'danger', 'text' => "Sizda tanga (coins) yetarli emas! Ushbu sovg'a uchun 🪙 {$item['coin_price']} tanga kerak."];
        }
    }
}

// Fetch active items
$items = $db->query("SELECT * FROM shop_items WHERE is_active=1 ORDER BY coin_price ASC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch student purchase history
$myRedemptions = $db->query("SELECT r.*, s.name as item_name, s.image_url FROM gift_redemptions r JOIN shop_items s ON r.item_id=s.id WHERE r.student_id=$student_id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ziyo Shop & Sovg'alar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; }
.table th { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
</style>
</head>
<body class="role-student">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🎁 Ziyo Shop Onlayn Do'kon</div>
            <div class="topbar-right">
                <div class="coin-balance-card" style="padding:6px 16px; border-radius:12px;">
                    <span>🪙 Balansingiz:</span>
                    <span class="coin-val" style="font-size:16px;"><?= number_format($student_coins) ?> Tanga</span>
                </div>
            </div>
        </div>

        <div class="page-content">

            <!-- Hero Banner -->
            <div class="shop-hero-header">
                <div>
                    <h2 style="font-size:22px; font-weight:900; color:#fff; margin-bottom:6px;">🛍️ Ziyo Store Express</h2>
                    <p style="color:var(--text-muted); font-size:13px; margin:0;">Testlarni a'lo baholarga yeching, coins yiging va ajoyib sovg'alarga ega bo'ling!</p>
                </div>
                <div class="coin-balance-card">
                    <span style="font-size:26px;">🪙</span>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; opacity:0.9;">Mavjud Balans</div>
                        <div class="coin-val"><?= number_format($student_coins) ?> Tanga</div>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg['type'] ?>" style="margin-bottom:20px; font-size:14px; padding:14px 18px; border-radius:14px;">
                    <?= $msg['text'] ?>
                </div>
            <?php endif; ?>

            <!-- Category Filter Pills -->
            <div class="shop-category-pills">
                <button class="cat-pill active" onclick="filterCategory('all', this)">⚡ Barcha Sovg'alar</button>
                <button class="cat-pill" onclick="filterCategory('Sovg\'alar', this)">🎁 Sovg'alar</button>
                <button class="cat-pill" onclick="filterCategory('Kantselyariya', this)">✏️ O'quv Qurollari</button>
                <button class="cat-pill" onclick="filterCategory('Chegirmalar', this)">🏷️ Kurs Chegirmalari</button>
                <button class="cat-pill" onclick="filterCategory('Boshqa', this)">✨ Boshqa</button>
            </div>

            <!-- Yandex Go Style Product Cards Grid -->
            <div class="yandex-shop-grid" id="productGrid">
                <?php foreach ($items as $item): ?>
                    <div class="yandex-card product-item-card" data-category="<?= htmlspecialchars($item['category'] ?? 'Sovg\'alar') ?>">
                        <div class="yandex-card-img-wrap">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                            <span class="yandex-card-badge">
                                <?= $item['stock_quantity'] > 0 ? 'Omborda: ' . $item['stock_quantity'] . ' ta' : 'Tugagan' ?>
                            </span>
                        </div>
                        <div class="yandex-card-body">
                            <div class="yandex-card-title"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="yandex-card-desc"><?= htmlspecialchars($item['description'] ?: 'ZiyoCRM o\'quvchilari uchun maxsus sovg\'a') ?></div>

                            <div class="yandex-card-footer">
                                <span class="yandex-price-pill">
                                    🪙 <?= number_format($item['coin_price']) ?>
                                </span>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="buy_item" class="yandex-buy-btn"
                                        <?= ($student_coins < $item['coin_price'] || $item['stock_quantity'] <= 0) ? 'disabled' : '' ?>>
                                        <?= $student_coins < $item['coin_price'] ? 'Coins Yetarli Emas' : ($item['stock_quantity'] <= 0 ? 'Mavjud Emas' : 'Sotib Olish 🛍️') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($items)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="es-icon">🛍️</div>
                        <h4>Do'konda mahsulotlar hozircha yo'q</h4>
                        <p>Tez orada admin yangi sovg'alar qo'shadi!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Order History -->
            <?php if (!empty($myRedemptions)): ?>
                <div class="card" style="margin-top:40px;">
                    <div class="card-header">
                        <h3>📦 Mening Buyurtmalarim Tarixi</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mahsulot</th>
                                    <th>Sarflandi</th>
                                    <th>Sana</th>
                                    <th>Buyurtma Holati</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myRedemptions as $myR): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <img src="<?= htmlspecialchars($myR['image_url']) ?>" style="width:38px; height:38px; object-fit:cover; border-radius:8px;">
                                                <span style="font-weight:700; font-size:13px;"><?= htmlspecialchars($myR['item_name']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="yandex-price-pill" style="font-size:12px; padding:3px 8px;">🪙 <?= $myR['coins_spent'] ?></span></td>
                                        <td style="font-size:12px; color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($myR['created_at'])) ?></td>
                                        <td>
                                            <?php if ($myR['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">Adminga topshirildi</span>
                                            <?php elseif ($myR['status'] === 'approved'): ?>
                                                <span class="badge badge-primary">Tasdiqlandi (Tayyorlanmoqda)</span>
                                            <?php elseif ($myR['status'] === 'delivered'): ?>
                                                <span class="badge badge-success">Topshirildi 🎉</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Rad etildi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function filterCategory(cat, btn) {
    document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.product-item-card').forEach(card => {
        if (cat === 'all' || card.getAttribute('data-category') === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
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
