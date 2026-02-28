<?php
// admin/posts.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
checkLogin();

$database = new Database();
$db = $database->getConnection();

// Silme İşlemi
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $db->query("ALTER TABLE posts AUTO_INCREMENT = 1");

    // Ajax isteği ise JSON dön
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    redirect('posts.php?msg=deleted');
}

// Durum Değiştirme (Taslak/Yayınla)
if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    $stmt = $db->prepare("UPDATE posts SET status = IF(status = 'published', 'draft', 'published') WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Ajax isteği ise JSON dön
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        $stmt = $db->prepare("SELECT status FROM posts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $new_status = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'new_status' => $new_status]);
        exit;
    }

    redirect('posts.php?msg=status_updated');
}

$query = "SELECT p.*, c.name as category_name 
          FROM posts p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC";
$posts = $db->query($query)->fetchAll();

$page_title = 'Yazı Yönetimi';
$active_page = 'posts';
require_once 'layout_header.php';
?>

<style>
    .card {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .btn {
        padding: 0.6rem 1.2rem;
        border-radius: 0.5rem;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .badge {
        padding: 0.25rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-published {
        background: #dcfce7;
        color: #166534;
    }

    .badge-draft {
        background: #f1f5f9;
        color: #475569;
    }

    .badge-featured {
        background: #fef9c3;
        color: #854d0e;
    }
</style>

<div class="header-actions">
    <h3>Tüm Yazılar</h3>
    <a href="post_add.php" class="btn btn-primary">
        <i data-lucide="plus"></i> Yeni Yazı Ekle
    </a>
</div>

<<div class="card">
    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border);">
                    <th style="padding: 1rem; text-align: left;">ID</th>
                    <th style="padding: 1rem; text-align: left;">Yazı</th>
                    <th style="padding: 1rem; text-align: left;">Kategori</th>
                    <th style="padding: 1rem; text-align: left;">Durum</th>
                    <th style="padding: 1rem; text-align: left;">İzlenme</th>
                    <th style="padding: 1rem; text-align: left;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?php echo $post['id']; ?></td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php if ($post['image']): ?>
                                    <img src="../uploads/<?php echo $post['image']; ?>"
                                        style="width: 40px; height: 40px; border-radius: 0.25rem; object-fit: cover;">
                                <?php endif; ?>
                                <div>
                                    <strong style="display: block;"><?php echo $post['title']; ?></strong>
                                    <span
                                        style="font-size: 0.75rem; color: var(--text-light);"><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem;"><?php echo $post['category_name'] ?? 'Kategorisiz'; ?></td>
                        <td style="padding: 1rem;">
                            <span class="badge badge-<?php echo $post['status']; ?>">
                                <?php echo $post['status'] === 'published' ? 'Yayında' : 'Taslak'; ?>
                            </span>
                            <?php if ($post['is_featured']): ?>
                                <span class="badge badge-featured">Öne Çıkan</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;"><?php echo $post['views_count']; ?></td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="javascript:void(0)" onclick="toggleStatus(<?php echo $post['id']; ?>, this)"
                                    title="<?php echo $post['status'] === 'published' ? 'Taslağa Çek' : 'Yayınla'; ?>"
                                    class="status-toggle-btn" data-id="<?php echo $post['id']; ?>"
                                    style="color: <?php echo $post['status'] === 'published' ? '#10b981' : '#94a3b8'; ?>;">
                                    <i data-lucide="<?php echo $post['status'] === 'published' ? 'eye' : 'eye-off'; ?>"
                                        size="18"></i>
                                </a>
                                <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="color: var(--primary);"><i
                                        data-lucide="edit-3" size="18"></i></a>
                                <a href="javascript:void(0)" onclick="deletePost(<?php echo $post['id']; ?>, this)"
                                    style="color: #ef4444;" title="Sil"><i data-lucide="trash-2" size="18"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="no-posts-row" style="<?php echo empty($posts) ? '' : 'display: none;'; ?>">
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);">Yazı
                        bulunamadı.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>

    <script>
        async function deletePost(id, el) {
            if (!confirm('Bu yazıyı silmek istediğinize emin misiniz?')) return;

            try {
                const response = await fetch('posts.php?delete=' + id, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    const row = el.closest('tr');
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    row.style.transition = 'all 0.3s ease';

                    setTimeout(() => {
                        row.remove();
                        // Eğer tablo boş kaldıysa mesajı göster
                        const tbody = document.querySelector('.table tbody');
                        const rows = tbody.querySelectorAll('tr:not(#no-posts-row)');
                        if (rows.length === 0) {
                            document.getElementById('no-posts-row').style.display = 'table-row';
                        }
                    }, 300);
                }
            } catch (error) {
                console.error('Hata:', error);
                alert('Silme işlemi sırasında bir hata oluştu.');
            }
        }

        async function toggleStatus(id, el) {
            const originalHTML = el.innerHTML;
            el.style.opacity = '0.5';
            el.style.pointerEvents = 'none';

            try {
                const response = await fetch('posts.php?toggle_status=' + id, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    const isPublished = data.new_status === 'published';
                    const badge = el.closest('tr').querySelector('.badge[class*="badge-"]'); // Select the status badge

                    // Icon ve Renk Güncelle
                    el.style.color = isPublished ? '#10b981' : '#94a3b8';
                    el.setAttribute('title', isPublished ? 'Taslağa Çek' : 'Yayınla');

                    // Lucide icon değiştirme (i veya svg'yi temizle ve yeni i ekle)
                    el.innerHTML = `<i data-lucide="${isPublished ? 'eye' : 'eye-off'}" size="18"></i>`;
                    lucide.createIcons(); // Re-render icon

                    // Badge Güncelle
                    if (badge) {
                        badge.className = 'badge badge-' + data.new_status;
                        badge.textContent = isPublished ? 'Yayında' : 'Taslak';
                    }
                }
            } catch (error) {
                console.error('Hata:', error);
                el.innerHTML = originalHTML;
                lucide.createIcons();
                alert('Durum güncellenirken bir hata oluştu.');
            } finally {
                el.style.opacity = '1';
                el.style.pointerEvents = 'auto';
            }
        }
    </script>

    <?php require_once 'layout_footer.php'; ?>