<?php
// admin/profile.php
$page_title = 'Profil Ayarları';
$active_page = 'profile';
require_once 'layout_header.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$admin_id = $_SESSION['admin_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = sanitize($_POST['username']);
        $new_password = $_POST['new_password'];
        $full_name = sanitize($_POST['full_name']);
        $bio = $_POST['bio'];
        $facebook_url = sanitize($_POST['facebook_url']);
        $instagram_url = sanitize($_POST['instagram_url']);

        // Resim Güncelleme
        $sql_image = "";
        $sql_password = "";

        $params = [
            ':username' => $username,
            ':full_name' => $full_name,
            ':bio' => $bio,
            ':facebook' => $facebook_url,
            ':instagram' => $instagram_url,
            ':id' => $admin_id
        ];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $image_name = "profile_" . $admin_id . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], "../uploads/" . $image_name)) {
                    $sql_image = ", profile_image = :profile_image";
                    $params[':profile_image'] = $image_name;
                }
            }
        }

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_password = ", password = :password";
            $params[':password'] = $hashed_password;
        }

        $sql = "UPDATE admins SET 
                username = :username,
                full_name = :full_name, 
                bio = :bio, 
                facebook_url = :facebook, 
                instagram_url = :instagram 
                $sql_image 
                $sql_password
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $stmt->execute($params);

        $_SESSION['admin_user'] = $username; // Session'ı da güncelle

        $message = '<div class="alert success">Profiliniz başarıyla güncellendi.</div>';
    } catch (Exception $e) {
        $message = '<div class="alert error">Hata oluştu: ' . $e->getMessage() . '</div>';
    }
}

// Mevcut bilgileri çek
$stmt = $db->prepare("SELECT * FROM admins WHERE id = :id");
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch();
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
    input[type="url"],
    textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 1rem;
    }

    .profile-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
        border: 3px solid var(--primary-light);
    }

    .social-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
        margin-top: 1rem;
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

    .alert.error {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
</style>

<div class="card">
    <h2>Profil Bilgileri</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Yazıların altında ve hakkında sayfasında görünecek
        bilgilerinizi buradan düzenleyebilirsiniz.</p>

    <?php echo $message; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group" style="text-align: center;">
            <label>Profil Resmi</label>
            <?php if ($admin['profile_image']): ?>
                <img src="../uploads/<?php echo $admin['profile_image']; ?>" class="profile-preview">
            <?php else: ?>
                <div class="profile-preview"
                    style="background: var(--primary-light); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; font-size: 3rem; color: var(--primary);">
                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <input type="file" name="profile_image" accept="image/*">
        </div>

        <div class="form-group">
            <label>Kullanıcı Adı (Giriş için)</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>"
                required>
        </div>

        <div class="form-group">
            <label>Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
            <input type="password" name="new_password" placeholder="••••••••">
        </div>

        <div class="form-group">
            <label>Tam Adınız</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>"
                placeholder="Ad Soyad">
        </div>

        <div class="form-group">
            <label>Hakkınızda (Kısa Biyografi)</label>
            <textarea name="bio" rows="4"
                placeholder="Kendinizden bahsedin..."><?php echo htmlspecialchars($admin['bio'] ?? ''); ?></textarea>
        </div>

        <h3 style="margin: 2rem 0 1rem 0;">Sosyal Medya Linkleri</h3>
        <div class="social-grid">
            <div class="form-group">
                <label>Instagram URL</label>
                <input type="url" name="instagram_url"
                    value="<?php echo htmlspecialchars($admin['instagram_url'] ?? ''); ?>"
                    placeholder="https://instagram.com/kullanici">
            </div>
            <div class="form-group">
                <label>Facebook URL</label>
                <input type="url" name="facebook_url"
                    value="<?php echo htmlspecialchars($admin['facebook_url'] ?? ''); ?>"
                    placeholder="https://facebook.com/kullanici">
            </div>
        </div>

        <button type="submit" class="btn-save">Profilimi Güncelle</button>
    </form>
</div>

<?php require_once 'layout_footer.php'; ?>