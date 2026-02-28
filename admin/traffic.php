<?php
// admin/traffic.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
checkLogin();

$database = new Database();
$db = $database->getConnection();

// Trafiği Temizle
if (isset($_GET['clear']) && $_GET['clear'] === 'all') {
    $db->query("DELETE FROM traffic");
    // Yazı okunmalarını da sıfırla (Tam temizlik)
    $db->query("UPDATE posts SET views_count = 0");

    // Ajax isteği ise JSON dön
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: traffic.php?cleared=1");
    exit;
}

// Son ziyaretçileri çek (Bot olmayanları göster)
$recent_traffic = $db->query("SELECT * FROM traffic ORDER BY visited_at DESC LIMIT 50")->fetchAll();

// En çok okunan yazılar (Top 5)
$popular_posts = $db->query("SELECT title, views_count FROM posts ORDER BY views_count DESC LIMIT 5")->fetchAll();

$page_title = 'Site Trafiği';
$active_page = 'traffic';
require_once 'layout_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 style="font-weight: 700; color: var(--text-dark);">Site İstatistikleri</h2>
    <a href="javascript:void(0)" onclick="clearLogs()" id="clearBtn"
        style="background: #ef4444; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
        <i data-lucide="trash-2" size="16"></i> Kayıtları Temizle
    </a>
</div>

<div id="clearMessage"
    style="display: none; background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border: 1px solid #bbf7d0;">
    Tüm trafik kayıtları başarıyla temizlendi.
</div>

<script>
    async function clearLogs() {
        if (!confirm('Tüm trafik kayıtlarını silmek istediğinize emin misiniz?')) return;

        const btn = document.getElementById('clearBtn');
        const originalHTML = btn.innerHTML;

        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';

        try {
            const response = await fetch('traffic.php?clear=all', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            if (data.success) {
                // Tabloyu temizle
                const tbody = document.querySelector('.table tbody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: var(--text-light);">Kayıt yok.</td></tr>';

                // Popüler yazıları sıfırla
                const popularList = document.getElementById('popularList');
                if (popularList) {
                    const badges = popularList.querySelectorAll('.badge');
                    badges.forEach(badge => badge.textContent = '0 izlenme');
                }

                // Başarı mesajını göster
                const msg = document.getElementById('clearMessage');
                msg.style.display = 'block';
                setTimeout(() => { msg.style.display = 'none'; }, 3000);
            }
        } catch (error) {
            console.error('Hata:', error);
            alert('Bir hata oluştu.');
        } finally {
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
    }
</script>

<style>
    .grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 992px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }

    .card {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 0.75rem;
        border-bottom: 1px solid var(--border);
        text-align: left;
    }

    .table th {
        color: var(--text-light);
        font-size: 0.875rem;
    }

    .popular-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .popular-item:last-child {
        border-bottom: none;
    }
</style>

<div class="grid">
    <div class="card">
        <h3>Son Ziyaretçiler</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>IP Adresi</th>
                        <th>Sayfa</th>
                        <th>Zaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_traffic as $t): ?>
                        <tr>
                            <td><code><?php echo $t['ip_address']; ?></code></td>
                            <td>
                                <?php echo $t['page_visited']; ?>
                            </td>
                            <td style="font-size: 0.75rem; color: var(--text-light);">
                                <?php echo date('d.m.Y H:i', strtotime($t['visited_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_traffic)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-light);">Kayıt yok.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>En Çok Okunanlar</h3>
        <div id="popularList" style="margin-top: 1rem;">
            <?php foreach ($popular_posts as $p): ?>
                <div class="popular-item">
                    <span style="font-weight: 500;">
                        <?php echo $p['title']; ?>
                    </span>
                    <span class="badge"
                        style="background: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.75rem;">
                        <?php echo $p['views_count']; ?> izlenme
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>