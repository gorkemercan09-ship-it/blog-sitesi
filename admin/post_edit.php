<?php
// admin/post_edit.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']))
    redirect('posts.php');
$id = (int) $_GET['id'];

// Yazıyı çek
$stmt = $db->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$post = $stmt->fetch();
if (!$post)
    redirect('posts.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = sanitize($_POST['title']);
        $content = $_POST['content'];
        $preview_text = sanitize($_POST['preview_text']);
        $category_id = (int) $_POST['category_id'];
        $status = $_POST['status'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = slugify($title);

        // Resim Güncelleme
        $image_name = $post['image'];
        $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';

        if ($remove_image) {
            $image_name = null;
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . "_" . $slug . "." . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image_name)) {
                throw new Exception("Dosya yüklenemedi.");
            }
        }

        $sql = "UPDATE posts SET 
                title = :title, 
                preview_text = :preview_text, 
                content = :content, 
                slug = :slug, 
                category_id = :category_id, 
                status = :status, 
                is_featured = :is_featured, 
                image = :image,
                author_id = :author_id 
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':preview_text' => $preview_text,
            ':content' => $content,
            ':slug' => $slug,
            ':category_id' => $category_id,
            ':status' => $status,
            ':is_featured' => $is_featured,
            ':image' => $image_name,
            ':author_id' => $post['author_id'] ?: $_SESSION['admin_id'],
            ':id' => $id
        ]);

        redirect('posts.php?success=updated');
    } catch (Exception $e) {
        $error = "Hata oluştu: " . $e->getMessage();
    }
}

$categories = $db->query("SELECT * FROM categories")->fetchAll();

$page_title = 'Yazı Düzenle';
$active_page = 'posts';
require_once 'layout_header.php';
?>

<?php if ($error): ?>
    <div
        style="background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; max-width: 900px; margin-left: auto; margin-right: auto;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/tn69m8xoy25gt910bn2vji2ftuoimeehftsnaj0r4r6wgpyj/tinymce/6/tinymce.min.js"
    referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        images_upload_url: 'upload_handler.php',
        automatic_uploads: true,
        height: 500,
        language: 'tr',
        relative_urls: false,
        remove_script_host: true,
        content_style: 'body { font-family:Outfit,sans-serif; font-size:16px }'
    });
</script>

<style>
    .card {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        max-width: 900px;
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
    select,
    textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 1rem;
    }

    textarea {
        min-height: 200px;
        line-height: 1.6;
    }

    .switch-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 700;
        cursor: pointer;
        font-size: 1rem;
        width: 100%;
        transition: opacity 0.2s;
    }

    .btn-submit:hover {
        opacity: 0.9;
    }

    /* Image Preview Styling */
    .image-preview-container {
        position: relative;
        width: 200px;
        height: 120px;
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 1rem;
        border: 2px solid var(--border);
        background: #f8fafc;
    }

    .image-preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-remove-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        cursor: pointer;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        gap: 0.5rem;
        z-index: 10;
    }

    .image-preview-container:hover .image-remove-overlay {
        opacity: 1;
    }
</style>

<div class="card">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Yazı Başlığı</label>
            <input type="text" name="title" id="title" required value="<?php echo $post['title']; ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="category_id">Kategori</label>
                <select name="category_id" id="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $post['category_id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Durum</label>
                <select name="status" id="status">
                    <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Yayında
                    </option>
                    <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Taslak</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="preview_text">Kısa Önizleme Metni</label>
            <textarea name="preview_text" id="preview_text"
                style="min-height: 80px;"><?php echo $post['preview_text']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Yazı İçeriği</label>
            <textarea name="content" id="content"><?php echo $post['content']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">Görsel (Değiştirmek istemiyorsanız boş bırakın)</label>
            <input type="hidden" name="remove_image" id="remove_image" value="0">
            <div id="image-preview" class="image-preview-container"
                style="<?php echo $post['image'] ? 'display: block;' : 'display: none;'; ?>">
                <img src="<?php echo $post['image'] ? '../uploads/' . $post['image'] : ''; ?>" id="preview-img">
                <div class="image-remove-overlay" onclick="removeImage()">
                    <i data-lucide="trash-2" size="18"></i> Kaldır
                </div>
            </div>
            <input type="file" name="image" id="image" accept="image/*" onchange="previewFile(this)">
        </div>

        <script>
            function previewFile(input) {
                const preview = document.getElementById('image-preview');
                const previewImg = document.getElementById('preview-img');
                const removeFlag = document.getElementById('remove_image');
                const file = input.files[0];
                const reader = new FileReader();

                reader.onloadend = function () {
                    previewImg.src = reader.result;
                    preview.style.display = 'block';
                    removeFlag.value = "0"; // Reset remove flag if new file selected
                }

                if (file) {
                    reader.readAsDataURL(file);
                } else if (!previewImg.getAttribute('src')) {
                    preview.style.display = 'none';
                }
            }

            function removeImage() {
                const input = document.getElementById('image');
                const preview = document.getElementById('image-preview');
                const previewImg = document.getElementById('preview-img');
                const removeFlag = document.getElementById('remove_image');

                input.value = ""; // Clear file input
                previewImg.src = "";
                preview.style.display = 'none';
                removeFlag.value = "1"; // Mark for deletion in DB
            }
        </script>

        <div class="form-group switch-group">
            <input type="checkbox" name="is_featured" id="is_featured" style="width: auto;" <?php echo $post['is_featured'] ? 'checked' : ''; ?>>
            <label for="is_featured" style="margin-bottom: 0;">Bu yazıyı ana sayfada öne çıkar</label>
        </div>

        <button type="submit" class="btn-submit">Değişiklikleri Kaydet</button>
    </form>
</div>

<?php require_once 'layout_footer.php'; ?>