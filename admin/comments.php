<?php
// admin/comments.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
checkLogin();

$database = new Database();
$db = $database->getConnection();

// Yorum Durumunu Değiştir (Ajax ile)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $stmt = $db->prepare("SELECT status FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();

    if ($current) {
        $new_status = ($current === 'approved') ? 'pending' : 'approved';
        $update = $db->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $update->execute([$new_status, $id]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'new_status' => $new_status]);
            exit;
        }
    }
    redirect('comments.php');
}

// Yorum Sil
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
    $db->query("ALTER TABLE comments AUTO_INCREMENT = 1");
    redirect('comments.php?msg=deleted');
}

// Yorumları Çek
$stmt = $db->query("SELECT c.*, p.title as post_title 
                    FROM comments c 
                    LEFT JOIN posts p ON c.post_id = p.id 
                    ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll();

$page_title = 'Yorumlar';
$active_page = 'comments';
require_once 'layout_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 style="font-weight: 700; color: var(--text-dark);">Yorum Yönetimi</h2>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        Yorum başarıyla silindi.
    </div>
<?php endif; ?>

<div class="comments-list" style="display: flex; flex-direction: column; gap: 1rem;">
    <?php foreach ($comments as $comment): ?>
        <div class="comment-card"
            style="background: white; border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; display: flex; gap: 1.5rem; align-items: flex-start; transition: all 0.2s;">
            <div class="comment-avatar" style="flex-shrink: 0;">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['name']); ?>&background=random&color=fff&size=48"
                    alt="Avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
            </div>
            <div class="comment-body" style="flex: 1; min-width: 0;">
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-dark);">
                            <?php echo htmlspecialchars($comment['name']); ?>
                        </h4>
                        <div style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.25rem;">
                            <a href="mailto:<?php echo htmlspecialchars($comment['email']); ?>"
                                style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($comment['email']); ?></a>
                            <span style="margin: 0 0.5rem;">•</span>
                            <?php echo date('d M Y, H:i', strtotime($comment['created_at'])); ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($comment['status'] === 'approved'): ?>
                            <span id="badge-<?php echo $comment['id']; ?>"
                                style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #d1fae5; color: #065f46;">Yayında</span>
                        <?php else: ?>
                            <span id="badge-<?php echo $comment['id']; ?>"
                                style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #fef3c7; color: #92400e;">Bekliyor</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div
                    style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; color: var(--text-dark); font-size: 0.95rem; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                    <div
                        style="font-size: 0.875rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="file-text" size="16"></i>
                        Yazı: <strong
                            style="color: var(--text-dark);"><?php echo htmlspecialchars($comment['post_title'] ?: 'Bilinmiyor'); ?></strong>
                    </div>
                    <div class="comment-actions" style="display: flex; gap: 0.5rem;">
                        <button onclick="toggleStatus(<?php echo $comment['id']; ?>, this)" class="btn-icon"
                            data-status="<?php echo $comment['status']; ?>"
                            title="<?php echo $comment['status'] === 'approved' ? 'Gizle (Onayı Kaldır)' : 'Onayla ve Yayınla'; ?>"
                            style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 0.375rem; border: 1px solid <?php echo $comment['status'] === 'approved' ? '#10b981' : '#f59e0b'; ?>; color: <?php echo $comment['status'] === 'approved' ? '#10b981' : '#f59e0b'; ?>; background: white; cursor: pointer; transition: all 0.2s;">
                            <i data-lucide="<?php echo $comment['status'] === 'approved' ? 'eye-off' : 'check'; ?>"
                                size="18"></i>
                        </button>

                        <a href="comments.php?delete=<?php echo $comment['id']; ?>" class="btn-icon btn-delete"
                            onclick="return confirm('Bu yorumu tamemen silmek istediğinize emin misiniz?')" title="Sil"
                            style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 0.375rem; border: 1px solid #ef4444; color: #ef4444; background: white; cursor: pointer; transition: all 0.2s;">
                            <i data-lucide="trash-2" size="18"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($comments)): ?>
        <div
            style="text-align: center; color: var(--text-light); padding: 4rem 2rem; background: white; border-radius: 0.75rem; border: 1px dashed var(--border-color);">
            <i data-lucide="message-square" size="48" style="margin-bottom: 1rem; opacity: 0.5;"></i>
            <h3>Henüz yorum bulunmuyor.</h3>
            <p>Yazılarınıza yapılan yorumlar burada listelenecektir.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    async function toggleStatus(id, el) {
        const originalHTML = el.innerHTML;
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';

        try {
            const response = await fetch('comments.php?toggle_status=' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.success) {
                const isApproved = data.new_status === 'approved';

                // Update Button
                el.style.color = isApproved ? '#ef4444' : '#10b981';
                el.style.borderColor = isApproved ? '#ef4444' : '#10b981';
                el.setAttribute('title', isApproved ? 'Gizle (Onayı Kaldır)' : 'Onayla ve Yayınla');
                el.innerHTML = `<i data-lucide="${isApproved ? 'eye-off' : 'check'}" size="18"></i>`;

                // Update Badge
                const badge = document.getElementById('badge-' + id);
                if (badge) {
                    if (isApproved) {
                        badge.style.background = '#d1fae5';
                        badge.style.color = '#065f46';
                        badge.innerText = 'Yayında';
                    } else {
                        badge.style.background = '#fef3c7';
                        badge.style.color = '#92400e';
                        badge.innerText = 'Bekliyor';
                    }
                }

                lucide.createIcons();
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
</script>

<?php require_once 'layout_footer.php'; ?>