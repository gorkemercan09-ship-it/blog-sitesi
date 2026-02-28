<?php
// admin/index.php
$page_title = 'Dashboard';
$active_page = 'dashboard';
require_once 'layout_header.php';
require_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

// İstatistikleri çek
$posts_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$cats_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_views = $db->query("SELECT SUM(views_count) FROM posts")->fetchColumn() ?? 0;
$traffic_today = $db->query("SELECT COUNT(*) FROM traffic WHERE DATE(visited_at) = CURDATE()")->fetchColumn();
?>

<!-- Welcome Section -->
<div
    style="margin-bottom: 2.5rem; background: var(--white); padding: 2rem; border-radius: 1.5rem; border: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; overflow: hidden; position: relative;">
    <div style="z-index: 2;">
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem;">Hoş Geldin,
            Admin! 👋</h1>
        <p style="color: var(--text-light); font-size: 1.125rem;">Blog siten bugün harika görünüyor. İşte son
            güncellemeler ve istatistikler.</p>
    </div>
    <div style="z-index: 2;">
        <a href="post_add.php"
            style="background: var(--primary); color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; transition: transform 0.2s;">
            <i data-lucide="plus" size="20"></i> Yeni Yazı Ekle
        </a>
    </div>
    <!-- Decorative Circle -->
    <div
        style="position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: var(--primary); opacity: 0.05; border-radius: 50%; z-index: 1;">
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .blue {
        background-color: var(--primary);
    }

    .purple {
        background-color: #8b5cf6;
    }

    .orange {
        background-color: #f59e0b;
    }

    .green {
        background-color: #10b981;
    }

    .stat-info h3 {
        font-size: 0.875rem;
        color: var(--text-light);
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .stat-info p {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    .recent-activity {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
    }

    .recent-activity h3 {
        margin-bottom: 1rem;
        font-size: 1.125rem;
        font-weight: 600;
    }

    .activity-list {
        list-style: none;
    }

    .activity-item {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border);
    }

    .activity-item:last-child {
        border-bottom: none;
    }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i data-lucide="file-text"></i></div>
        <div class="stat-info">
            <h3>Toplam Yazı</h3>
            <p id="stat-posts">
                <?php echo $posts_count; ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i data-lucide="tag"></i></div>
        <div class="stat-info">
            <h3>Kategoriler</h3>
            <p id="stat-cats">
                <?php echo $cats_count; ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="eye"></i></div>
        <div class="stat-info">
            <h3>Toplam Okunma</h3>
            <p id="stat-views">
                <?php echo $total_views; ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i data-lucide="trending-up"></i></div>
        <div class="stat-info" style="flex-grow: 1;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3>Günlük Trafik</h3>
                    <p id="stat-traffic">
                        <?php echo $traffic_today; ?>
                    </p>
                </div>
                <button onclick="clearTraffic(this)" title="Temizle"
                    style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem;">
                    <i data-lucide="trash-2" size="16"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="recent-activity">
    <h3>Son Eklenen Yazılar</h3>
    <div class="activity-list">
        <?php
        $recent_posts = $db->query("SELECT id, title, created_at, status FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
        if ($recent_posts):
            foreach ($recent_posts as $post):
                ?>
                <div class="activity-item">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button onclick="toggleStatus(<?php echo $post['id']; ?>, this)" class="status-btn"
                            title="<?php echo $post['status'] === 'published' ? 'Gizle' : 'Göster'; ?>"
                            style="background: none; border: none; cursor: pointer; color: <?php echo $post['status'] === 'published' ? '#10b981' : '#94a3b8'; ?>;">
                            <i data-lucide="<?php echo $post['status'] === 'published' ? 'eye' : 'eye-off'; ?>" size="18"></i>
                        </button>
                        <span>
                            <?php echo $post['title']; ?>
                        </span>
                    </div>
                    <span style="color: var(--text-light); font-size: 0.875rem;">
                        <?php echo date('d.m.Y', strtotime($post['created_at'])); ?>
                    </span>
                </div>
            <?php endforeach; else: ?>
            <p style="color: var(--text-light); text-align: center; padding: 1rem;">Henüz yazı bulunmuyor.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    async function refreshStats() {
        try {
            const response = await fetch('api_stats.php');
            const data = await response.json();

            document.getElementById('stat-posts').textContent = data.posts_count;
            document.getElementById('stat-cats').textContent = data.cats_count;
            document.getElementById('stat-views').textContent = data.total_views;
            document.getElementById('stat-traffic').textContent = data.traffic_today;
        } catch (error) {
            console.error('Stats refresh failed:', error);
        }
    }

    async function toggleStatus(id, el) {
        const originalHTML = el.innerHTML;
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';

        try {
            const response = await fetch('posts.php?toggle_status=' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.success) {
                const isPublished = data.new_status === 'published';
                el.style.color = isPublished ? '#10b981' : '#94a3b8';
                el.setAttribute('title', isPublished ? 'Gizle' : 'Göster');

                // Iconu yenile
                el.innerHTML = `<i data-lucide="${isPublished ? 'eye' : 'eye-off'}" size="18"></i>`;
                lucide.createIcons();

                refreshStats(); // İstatistikleri güncelle
            }
        } catch (error) {
            console.error('Error:', error);
            el.innerHTML = originalHTML;
            lucide.createIcons();
            alert('İşlem sırasında bir hata oluştu.');
        } finally {
            el.style.opacity = '1';
            el.style.pointerEvents = 'auto';
        }
    }

    async function clearTraffic(btn) {
        if (!confirm('Tüm istatistikleri ve trafik kayıtlarını temizlemek istediğinize emin misiniz?')) return;

        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';

        try {
            const response = await fetch('traffic.php?clear=all', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.success) {
                refreshStats();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Trafik temizlenirken bir hata oluştu.');
        } finally {
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
    }

    // Her 30 saniyede bir istatistikleri güncelle (Opsiyonel ama hoş bir dokunuş)
    // setInterval(refreshStats, 30000);
</script>

<?php require_once 'layout_footer.php'; ?>