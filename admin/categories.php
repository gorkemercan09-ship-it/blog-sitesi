<?php
// admin/categories.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
checkLogin();

$database = new Database();
$db = $database->getConnection();

// Kategori Ekleme
if (isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $slug = slugify($name);

    $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':slug', $slug);
    $stmt->execute();
    redirect('categories.php?success=1');
}

// Kategori Silme
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Reset auto-increment so that if the table is empty it restarts at 1
    // If not empty, it will set to MAX(id) + 1.
    $db->query("ALTER TABLE categories AUTO_INCREMENT = 1");

    // Ajax isteği ise JSON dön
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    redirect('categories.php?success=2');
}

$categories = $db->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

$page_title = 'Kategori Yönetimi';
$active_page = 'categories';
require_once 'layout_header.php';
?>

<style>
    .card {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        margin-bottom: 2rem;
    }

    .form-inline {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        margin-bottom: 2rem;
    }

    .form-group {
        flex-grow: 1;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: var(--text-light);
    }

    input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border);
        text-align: left;
    }

    .table th {
        color: var(--text-light);
        font-weight: 500;
        font-size: 0.875rem;
    }

    .btn-danger {
        color: #ef4444;
        background: transparent;
        padding: 0.5rem;
    }
</style>

<div class="card">
    <h3>Yeni Kategori Ekle</h3>
    <form action="" method="POST" class="form-inline">
        <div class="form-group">
            <label>Kategori Adı</label>
            <input type="text" name="name" required placeholder="Örn: Teknoloji">
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">
            <i data-lucide="plus"></i> Ekle
        </button>
    </form>
</div>

<div class="card">
    <h3>Kategoriler</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>İsim</th>
                <th>Slug</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><strong><?php echo $cat['name']; ?></strong></td>
                    <td><code><?php echo $cat['slug']; ?></code></td>
                    <td>
                        <a href="javascript:void(0)" onclick="deleteCategory(<?php echo $cat['id']; ?>, this)"
                            class="btn-danger" title="Sil">
                            <i data-lucide="trash-2"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="no-categories-row" style="<?php echo empty($categories) ? '' : 'display: none;'; ?>">
                <td colspan="4" style="text-align: center; color: var(--text-light);">Henüz kategori eklenmemiş.</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    async function deleteCategory(id, el) {
        if (!confirm('Bu kategoriyi silmek istediğinize emin misiniz?')) return;

        try {
            const response = await fetch('categories.php?delete=' + id, {
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
                    const rows = tbody.querySelectorAll('tr:not(#no-categories-row)');
                    if (rows.length === 0) {
                        document.getElementById('no-categories-row').style.display = 'table-row';
                    }
                }, 300);
            }
        } catch (error) {
            console.error('Hata:', error);
            alert('Silme işlemi sırasında bir hata oluştu.');
        }
    }
</script>

<?php require_once 'layout_footer.php'; ?>