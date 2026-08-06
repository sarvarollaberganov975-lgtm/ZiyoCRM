<?php
// ==============================================
// ZiyoCRM - Ziyo Shop Admin Management
// ==============================================
require_once __DIR__ . '/../includes/config.php';
requireLogin('admin');

$db = getDB();
$user = getCurrentUser();
$msg = $err = '';

// Check and add category column if missing in SQLite
try {
    $db->exec("ALTER TABLE shop_items ADD COLUMN category TEXT DEFAULT 'Sovg''alar'");
} catch (Exception $e) {
    // Column already exists
}

// Upload directory setup
$upload_dir = __DIR__ . '/../assets/uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Clear demo items if requested or on clear action
if (isset($_GET['clear_demo'])) {
    $db->exec("DELETE FROM shop_items");
    header("Location: manage_shop.php?msg=cleared");
    exit;
}

// Add new shop product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shop_item'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $coin_price = (int)($_POST['coin_price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 10);
    $category = trim($_POST['category'] ?? 'Sovg\'alar');
    $image_url = trim($_POST['image_url'] ?? '');

    // Handle File Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['image_file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($file_tmp, $target_path)) {
            $image_url = '../assets/uploads/' . $file_name;
        }
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=500&auto=format&fit=crop&q=60';
    }

    if ($name && $coin_price > 0) {
        $stmt = $db->prepare("INSERT INTO shop_items (name, description, image_url, coin_price, stock_quantity, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $image_url, $coin_price, $stock_quantity, $category]);
        $msg = "✅ Yangi mahsulot do'konga qo'shildi!";
    } else {
        $err = "❌ Iltimos, mahsulot nomi va tangadagi narxini to'g'ri kiriting!";
    }
}

// Delete item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $item_id = (int)$_POST['item_id'];
    $db->prepare("DELETE FROM shop_items WHERE id=?")->execute([$item_id]);
    $msg = "🗑️ Mahsulot o'chirildi!";
}

// Update redemption status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_redemption'])) {
    $redemption_id = (int)$_POST['redemption_id'];
    $new_status = $_POST['status']; // approved, delivered, rejected

    $stmt = $db->prepare("UPDATE gift_redemptions SET status = ?, approved_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_status, $redemption_id]);

    $msg = "✅ Buyurtma holati yangilandi!";
}

// Fetch Redemptions
$redemptions = $db->query("SELECT r.*, u.full_name as student_name, s.name as item_name, s.image_url FROM gift_redemptions r JOIN users u ON r.student_id=u.id JOIN shop_items s ON r.item_id=s.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Shop Items
$items = $db->query("SELECT * FROM shop_items ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ziyo Shop & Sovg'alar Boshqaruvi — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.shop-item-row-img {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
    background: var(--dark4);
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    text-align: left;
}
.table th {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
}
</style>
</head>
<body class="role-admin">
<div class="dashboard">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🎁 Ziyo Shop & Sovg'alar Boshqaruvi</div>
            <div class="topbar-right" style="display:flex; gap:10px;">
                <a href="manage_shop.php?clear_demo=1" onclick="return confirm('Barcha namuna sovg\'alarni tozalashni xohlaysizmi?')" class="btn btn-danger btn-sm">
                    🗑️ Barcha Namunaviy Sovg'alarni O'chirish
                </a>
                <button class="btn btn-warning btn-sm" onclick="document.getElementById('addItemModal').classList.add('show')">
                    ➕ Yangi Mahsulot Qo'shish
                </button>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <!-- Grid 2: Do'kondagi tovarlar & Buyurtmalar -->
            <div class="grid-2" style="grid-template-columns: 1fr 1.2fr; gap: 24px;">

                <!-- Do'kondagi tovarlar -->
                <div class="card">
                    <div class="card-header">
                        <h3>🛍️ Do'kondagi Mahsulotlar (<?= count($items) ?>)</h3>
                        <button class="btn btn-sm btn-outline" onclick="document.getElementById('addItemModal').classList.add('show')">➕ Qo'shish</button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mahsulot</th>
                                    <th>Kategoriya</th>
                                    <th>Narx</th>
                                    <th>Omborda</th>
                                    <th>Amal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <img src="<?= htmlspecialchars($it['image_url']) ?>" class="shop-item-row-img" alt="">
                                                <div>
                                                    <div style="font-weight:700; font-size:13px;"><?= htmlspecialchars($it['name']) ?></div>
                                                    <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars(substr($it['description']??'',0,30)) ?>...</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-gray"><?= htmlspecialchars($it['category'] ?? 'Sovg\'alar') ?></span></td>
                                        <td><span class="yandex-price-pill" style="font-size:12px; padding:3px 8px;">🪙 <?= $it['coin_price'] ?></span></td>
                                        <td><span class="badge <?= $it['stock_quantity'] > 0 ? 'badge-success' : 'badge-danger' ?>"><?= $it['stock_quantity'] ?> dona</span></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Ushbu mahsulotni o\'chirmoqchimisiz?');">
                                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                                <button type="submit" name="delete_item" class="btn btn-sm btn-danger" style="padding:4px 8px; font-size:11px;">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($items)): ?>
                                    <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px;">Do'konda xali mahsulotlar yo'q.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kelib tushgan buyurtmalar -->
                <div class="card">
                    <div class="card-header">
                        <h3>📦 Kelib Tushgan Buyurtmalar (<?= count($redemptions) ?>)</h3>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>O'quvchi</th>
                                    <th>Mahsulot</th>
                                    <th>Sarflandi</th>
                                    <th>Holat</th>
                                    <th>Boshqaruv</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($redemptions as $red): ?>
                                    <tr>
                                        <td style="font-weight:700; color:#93c5fd; font-size:13px;"><?= htmlspecialchars($red['student_name']) ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <img src="<?= htmlspecialchars($red['image_url']) ?>" style="width:32px; height:32px; border-radius:6px; object-fit:cover;">
                                                <span style="font-size:13px; font-weight:600;"><?= htmlspecialchars($red['item_name']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="yandex-price-pill" style="font-size:11px; padding:2px 6px;">🪙 <?= $red['coins_spent'] ?></span></td>
                                        <td>
                                            <?php if($red['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">Kutilmoqda</span>
                                            <?php elseif($red['status'] === 'approved'): ?>
                                                <span class="badge badge-primary">Tasdiqlandi</span>
                                            <?php elseif($red['status'] === 'delivered'): ?>
                                                <span class="badge badge-success">Topshirildi 🎉</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Rad etildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:flex; gap:4px;">
                                                <input type="hidden" name="redemption_id" value="<?= $red['id'] ?>">
                                                <input type="hidden" name="update_redemption" value="1">
                                                <?php if($red['status'] === 'pending'): ?>
                                                    <button type="submit" name="status" value="approved" class="btn btn-sm btn-primary" style="padding:4px 8px; font-size:11px;">Tasdiqlash</button>
                                                    <button type="submit" name="status" value="rejected" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size:11px; color:#ef4444;">Rad etish</button>
                                                <?php elseif($red['status'] === 'approved'): ?>
                                                    <button type="submit" name="status" value="delivered" class="btn btn-sm btn-success" style="padding:4px 8px; font-size:11px;">Topshirildi ✅</button>
                                                <?php else: ?>
                                                    <span style="font-size:11px; color:var(--text-muted);">Bajarildi</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($redemptions)): ?>
                                    <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px;">Xali sovg'a buyurtmalari kelib tushmadi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Modal: Yangi Mahsulot Qo'shish -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🎁 Yangi Mahsulot Qo'shish</h3>
            <button class="modal-close" onclick="document.getElementById('addItemModal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_shop_item" value="1">

            <div class="form-group">
                <label>Mahsulot Nomi</label>
                <input type="text" name="name" placeholder="Masalan: Ziyo CRM Bloknot" required style="padding-left:14px;">
            </div>

            <div class="form-group">
                <label>Kategoriya</label>
                <select name="category" style="padding-left:14px;">
                    <option value="Sovg'alar">🎁 Sovg'alar</option>
                    <option value="Kantselyariya">✏️ O'quv Qurollari</option>
                    <option value="Chegirmalar">🏷️ Kurs Chegirmalari</option>
                    <option value="Boshqa">✨ Boshqa</option>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Narxi (🪙 Coins / Tanga)</label>
                    <input type="number" name="coin_price" value="50" min="1" required style="padding-left:14px;">
                </div>
                <div class="form-group">
                    <label>Ombordagi Soni</label>
                    <input type="number" name="stock_quantity" value="10" min="1" required style="padding-left:14px;">
                </div>
            </div>

            <div class="form-group">
                <label>Mahsulot Rasmi (Fayl yuklash)</label>
                <input type="file" name="image_file" accept="image/*" style="padding-left:14px;">
            </div>

            <div class="form-group">
                <label>Yoki Rasm URL (Ixtiyoriy)</label>
                <input type="url" name="image_url" placeholder="https://..." style="padding-left:14px;">
            </div>

            <div class="form-group">
                <label>Tavsif / Ma'lumot</label>
                <textarea name="description" placeholder="Mahsulot haqida qisqacha..." style="padding-left:14px; min-height:70px;"></textarea>
            </div>

            <button type="submit" class="btn btn-warning btn-lg" style="width:100%; margin-top:10px;">💾 Do'konga Saqlash</button>
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
