<?php
// admin/settings.php
$page_title = 'Site Ayarları';
$active_page = 'settings';
require_once 'layout_header.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        $stmt->execute(['value' => $value, 'key' => $key]);
    }
    $message = '<div class="alert success">Ayarlar başarıyla güncellendi.</div>';
}

$settings = [];
$rows = $db->query("SELECT * FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<style>
    .card {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        max-width: 800px;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-dark);
    }

    input[type="text"],
    textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 1rem;
    }

    .btn-save {
        background: var(--primary);
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
    }

    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
</style>

<div class="card">
    <h2>
        <?php echo $page_title; ?>
    </h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Sitenizin Google aramalarındaki görünümünü ve genel
        ayarlarını buradan yönetebilirsiniz.</p>

    <?php echo $message; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Site Başlığı (SEO Title)</label>
            <input type="text" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>"
                placeholder="Örn: Modern Teknoloji Bloğu">
        </div>

        <div class="form-group">
            <label>Site Açıklaması (Meta Description)</label>
            <textarea name="site_description"
                rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Anahtar Kelimeler (Kelimeleri virgül ile ayırın)</label>
            <input type="text" name="site_keywords"
                value="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Google Analytics Kodu (İsteğe bağlı)</label>
            <input type="text" name="ga_code" value="<?php echo htmlspecialchars($settings['ga_code'] ?? ''); ?>"
                placeholder="UA-XXXXX-Y veya G-XXXXXX">
        </div>

        <div class="form-group">
            <label>Varsayılan Paylaşım Görseli (URL)</label>
            <input type="text" name="og_image" value="<?php echo htmlspecialchars($settings['og_image'] ?? ''); ?>"
                placeholder="https://siteniz.com/logo.png">
        </div>

        <button type="submit" class="btn-save">Ayarları Kaydet</button>
    </form>
</div>

<?php require_once 'layout_footer.php'; ?>