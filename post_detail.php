<?php
// post_detail.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug']))
    redirect('index.php');
$slug = $_GET['slug'];

$database = new Database();
$db = $database->getConnection();

// Yazıyı çek
$stmt = $db->prepare("SELECT p.*, c.name as category_name, a.full_name as author_name, a.profile_image as author_image, a.bio as author_bio, a.facebook_url, a.instagram_url 
                      FROM posts p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN admins a ON p.author_id = a.id 
                      WHERE p.slug = :slug AND p.status = 'published'");
$stmt->bindParam(':slug', $slug);
$stmt->execute();
$post = $stmt->fetch();

if (!$post)
    redirect('index.php');

// Yorum Ekleme İşlemi
$comment_msg = '';
$comment_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $comment_text = sanitize($_POST['comment'] ?? '');

    if (empty($name) || empty($comment_text)) {
        $comment_msg = 'Lütfen isim ve yorum alanlarını doldurun.';
        $comment_type = 'error';
    } else {
        // CSRF vb. eklenebilir. Basit tutuyoruz.
        $stmt = $db->prepare("INSERT INTO comments (post_id, name, email, comment, status) VALUES (:post_id, :name, :email, :comment, 'pending')");
        // Not: Varsayılan olarak pending yapıyoruz
        if (
            $stmt->execute([
                ':post_id' => $post['id'],
                ':name' => $name,
                ':email' => $email,
                ':comment' => $comment_text
            ])
        ) {
            // Bildirim eklendi
            $notif_title = "Yeni Yorum: " . mb_substr(htmlspecialchars($post['title']), 0, 30) . "...";
            $notif_msg = htmlspecialchars($name) . " kullanıcısından onay bekleyen yeni bir yorum var.";
            $notif_link = "comments.php";
            $db->prepare("INSERT INTO notifications (title, message, link) VALUES (?, ?, ?)")->execute([$notif_title, $notif_msg, $notif_link]);

            $comment_msg = 'Yorumunuz başarıyla gönderildi. Yönetici onayının ardından yayınlanacaktır.';
            $comment_type = 'success';
            // Post işlemi bittiğinde formu temizlemek için redirect yap (PRG pattern)
            header("Location: post_detail.php?slug=" . $post['slug'] . "#comments-section");
            exit;
        } else {
            $comment_msg = 'Bir hata oluştu.';
            $comment_type = 'error';
        }
    }
}

// Yorumları Çek ve Filtrele
$sort_order = 'DESC'; // Varsayılan: En yeni
if (isset($_GET['sort']) && $_GET['sort'] === 'oldest') {
    $sort_order = 'ASC';
}

$stmt = $db->prepare("SELECT * FROM comments WHERE post_id = :post_id AND status = 'approved' ORDER BY created_at $sort_order");
$stmt->bindParam(':post_id', $post['id']);
$stmt->execute();
$comments = $stmt->fetchAll();
$comment_count = count($comments);



// İzlenme sayısını artır ve Trafiği logla
if (logTraffic($db, 'Post: ' . $post['title'])) {
    $stmt = $db->prepare("UPDATE posts SET views_count = views_count + 1 WHERE id = :id");
    $stmt->bindParam(':id', $post['id']);
    $stmt->execute();
}

// Tavsiye Yazılar (Aynı kategoriden)
$stmt = $db->prepare("SELECT * FROM posts WHERE category_id = :cat AND id != :id AND status = 'published' LIMIT 3");
$stmt->bindParam(':cat', $post['category_id']);
$stmt->bindParam(':id', $post['id']);
$stmt->execute();
$recommended = $stmt->fetchAll();

// Okuma Süresi Hesaplama (Yaklaşık)
$word_count = str_word_count(strip_tags($post['content']));
$reading_time = ceil($word_count / 200); // Dakikada 200 kelime

// Site Ayarlarını Çek
$site_title = getSetting('site_title') ?: 'Antigravity Blog';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo generateSeoTags($post['title'], $post['preview_text'], "uploads/" . $post['image'], 'article'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #e11d48;
            --primary-light: #ffe4e6;
            --dark: #1e1b4b;
            --light: #f8fafc;
            --gray: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: #ffffff;
            color: var(--dark);
            line-height: 1.8;
        }

        /* Progress Bar */
        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: transparent;
            z-index: 1000;
        }

        .progress-bar {
            height: 4px;
            background: var(--primary);
            width: 0%;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        nav {
            padding: 1rem 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            text-decoration: none;
        }

        .logo span {
            color: var(--primary);
        }

        /* Article Layout */
        .article-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 4rem;
            margin-top: 3rem;
        }

        .post-header {
            margin-bottom: 2.5rem;
        }

        .post-category {
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            background: var(--primary-light);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
        }

        .post-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin: 1.5rem 0;
            color: var(--dark);
        }

        .post-meta {
            display: flex;
            gap: 2rem;
            color: var(--gray);
            font-size: 0.875rem;
            align-items: center;
        }

        .main-img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .post-content {
            font-size: 1.25rem;
            color: #1e293b;
            text-align: justify;
        }

        .post-content p {
            margin-bottom: 2rem;
        }

        /* Social Share Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 3rem;
        }

        .author-box {
            background: var(--light);
            padding: 2rem;
            border-radius: 1.5rem;
            text-align: center;
        }

        .author-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--white);
        }

        .share-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.875rem;
            transition: transform 0.2s;
        }

        .share-btn:hover {
            transform: translateY(-2px);
        }

        .btn-twitter {
            background: #1DA1F2;
            color: white;
        }

        .btn-facebook {
            background: #1877F2;
            color: white;
        }

        /* Recommended Section */
        .recommended {
            margin-top: 6rem;
            padding: 4rem 0;
            background: var(--light);
            border-radius: 3rem;
        }

        .rec-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .rec-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .rec-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .rec-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        @media (max-width: 1024px) {
            .article-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: 2;
                flex-direction: row;
                flex-wrap: wrap;
            }

            .author-box,
            .share-buttons {
                flex: 1;
                min-width: 250px;
            }

            .post-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .rec-grid {
                grid-template-columns: 1fr;
            }

            .main-img {
                height: 300px;
            }
        }

        /* Comments Styles */
        .comments-section {
            margin-top: 5rem;
            padding-top: 4rem;
            border-top: 1px solid var(--border);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .comment-filters a {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            background: var(--light);
            transition: all 0.2s;
        }

        .comment-filters a.active {
            background: var(--primary);
            color: white;
        }

        .comment-form {
            background: var(--light);
            padding: 2rem;
            border-radius: 1.5rem;
            margin-bottom: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-comment {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }

        .btn-comment:hover {
            transform: translateY(-2px);
            background: #1d4ed8;
        }

        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .comment-item {
            display: flex;
            gap: 1.5rem;
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .comment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .comment-author {
            font-weight: 700;
            color: var(--dark);
        }

        .comment-date {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .comment-text {
            color: #334155;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="progress-container">
        <div class="progress-bar" id="readingProgress"></div>
    </div>

    <nav>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo"><i data-lucide="zap"></i> <?php
            $title_parts = explode(' ', $site_title, 2);
            echo htmlspecialchars($title_parts[0]);
            if (isset($title_parts[1]))
                echo "<span>" . htmlspecialchars($title_parts[1]) . "</span>";
            ?></a>
            <div style="display: flex; gap: 2rem; align-items: center;">
                <a href="index.php" style="text-decoration: none; color: var(--dark); font-weight: 500;">Ana Sayfa</a>
                <a href="categories.php"
                    style="text-decoration: none; color: var(--dark); font-weight: 500;">Kategoriler</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="article-layout">
            <main>
                <header class="post-header">
                    <span class="post-category"><?php echo $post['category_name']; ?></span>
                    <h1 class="post-title"><?php echo $post['title']; ?></h1>
                    <div class="post-content-wrapper">
                        <article>
                            <header class="post-header">
                                <span class="post-category"><?php echo $post['category_name']; ?></span>
                                <h1 class="post-title"><?php echo $post['title']; ?></h1>
                                <div class="post-meta">
                                    <span style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php if ($post['author_image']): ?>
                                            <img src="uploads/<?php echo $post['author_image']; ?>"
                                                style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <i data-lucide="user" size="18"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($post['author_name'] ?: 'Yazar'); ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i
                                            data-lucide="calendar" size="18"></i>
                                        <?php echo date('d F, Y', strtotime($post['created_at'])); ?></span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i
                                            data-lucide="clock" size="18"></i> <?php echo $reading_time; ?> dak
                                        okuma</span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="eye"
                                            size="18"></i> <?php echo $post['views_count']; ?></span>
                                </div>
                            </header>

                            <?php if ($post['image']): ?>
                                <img src="uploads/<?php echo $post['image']; ?>" class="main-img">
                            <?php endif; ?>

                            <div class="post-content">
                                <?php echo $post['content']; ?>
                            </div>

                            <!-- Yorum Bölümü -->
                            <section class="comments-section" id="comments-section">
                                <div class="comment-header">
                                    <h3 style="font-size: 1.75rem; font-weight: 800;">Yorumlar
                                        (<?php echo $comment_count; ?>)</h3>
                                    <div class="comment-filters" style="display: flex; gap: 0.5rem;">
                                        <a href="?slug=<?php echo $post['slug']; ?>&sort=newest#comments-section"
                                            class="<?php echo $sort_order === 'DESC' ? 'active' : ''; ?>">En Yeni</a>
                                        <a href="?slug=<?php echo $post['slug']; ?>&sort=oldest#comments-section"
                                            class="<?php echo $sort_order === 'ASC' ? 'active' : ''; ?>">En Eski</a>
                                    </div>
                                </div>

                                <?php if ($comment_msg): ?>
                                    <div
                                        style="padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; <?php echo $comment_type === 'success' ? 'background:#dcfce7; color:#166534;' : 'background:#fee2e2; color:#991b1b;'; ?>">
                                        <?php echo $comment_msg; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Yorum Formu -->
                                <div class="comment-form">
                                    <h4 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Bir Yorum Bırakın</h4>
                                    <form method="POST"
                                        action="post_detail.php?slug=<?php echo $post['slug']; ?>#comments-section">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div class="form-group">
                                                <input type="text" name="name" class="form-control"
                                                    placeholder="İsminiz *" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="email" name="email" class="form-control"
                                                    placeholder="E-posta Adresiniz (İsteğe bağlı)">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <textarea name="comment" class="form-control" rows="5"
                                                placeholder="Yorumunuzu buraya yazın... *" required></textarea>
                                        </div>
                                        <button type="submit" name="submit_comment" class="btn-comment">Yorum
                                            Gönder</button>
                                    </form>
                                </div>

                                <!-- Yorum Listesi -->
                                <div class="comment-list">
                                    <?php if ($comments): ?>
                                        <?php foreach ($comments as $c): ?>
                                            <div class="comment-item">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c['name']); ?>&background=f1f5f9&color=64748b"
                                                    class="comment-avatar">
                                                <div style="flex-grow: 1;">
                                                    <div class="comment-meta">
                                                        <span
                                                            class="comment-author"><?php echo htmlspecialchars($c['name']); ?></span>
                                                        <span
                                                            class="comment-date"><?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?></span>
                                                    </div>
                                                    <div class="comment-text">
                                                        <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="text-align: center; color: var(--gray); font-style: italic;">İlk yorumu
                                            siz yapın!
                                        </p>
                                    <?php endif; ?>
                                    <h4 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Bir Yorum Bırakın</h4>
                                    <form method="POST"
                                        action="post_detail.php?slug=<?php echo $post['slug']; ?>#comments-section">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div class="form-group">
                                                <input type="text" name="name" class="form-control"
                                                    placeholder="İsminiz *" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="email" name="email" class="form-control"
                                                    placeholder="E-posta Adresiniz (İsteğe bağlı)">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <textarea name="comment" class="form-control" rows="5"
                                                placeholder="Yorumunuzu buraya yazın... *" required></textarea>
                                        </div>
                                        <button type="submit" name="submit_comment" class="btn-comment">Yorum
                                            Gönder</button>
                                    </form>
                                </div>

                                <!-- Yorum Listesi -->
                                <div class="comment-list">
                                    <?php if ($comments): ?>
                                        <?php foreach ($comments as $c): ?>
                                            <div class="comment-item">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c['name']); ?>&background=f1f5f9&color=64748b"
                                                    class="comment-avatar">
                                                <div style="flex-grow: 1;">
                                                    <div class="comment-meta">
                                                        <span
                                                            class="comment-author"><?php echo htmlspecialchars($c['name']); ?></span>
                                                        <span
                                                            class="comment-date"><?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?></span>
                                                    </div>
                                                    <div class="comment-text">
                                                        <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="text-align: center; color: var(--gray); font-style: italic;">İlk yorumu
                                            siz yapın!</p>
                                    <?php endif; ?>
                                </div>
                            </section>
            </main>

            <aside class="sidebar">
                <div class="author-box">
                    <h5 style="font-size: 0.75rem; color: var(--primary); margin-bottom: 1rem; letter-spacing: 1px;">
                        Yazar hakkında</h5>
                    <?php if ($post['author_image']): ?>
                        <img src="uploads/<?php echo $post['author_image']; ?>" class="author-img">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['author_name'] ?: 'Admin'); ?>&background=2563eb&color=fff"
                            class="author-img">
                    <?php endif; ?>

                    <h4 style="margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($post['author_name'] ?: ($site_title . ' Yazarı')); ?>
                    </h4>
                    <p style="font-size: 0.875rem; color: var(--gray); margin-bottom: 1.5rem;">
                        <?php echo nl2br(htmlspecialchars($post['author_bio'] ?: 'Teknoloji ve modern tasarım tutkunu.')); ?>
                    </p>

                    <div style="display: flex; justify-content: center; gap: 1rem;">
                        <?php if ($post['instagram_url']): ?>
                            <a href="<?php echo $post['instagram_url']; ?>" target="_blank" style="color: var(--gray);"><i
                                    data-lucide="instagram" size="18"></i></a>
                        <?php endif; ?>
                        <?php if ($post['facebook_url']): ?>
                            <a href="<?php echo $post['facebook_url']; ?>" target="_blank" style="color: var(--gray);"><i
                                    data-lucide="facebook" size="18"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="share-buttons">
                    <h4 style="font-size: 1rem; margin-bottom: 1rem;">Paylaşın</h4>
                    <a href="https://www.instagram.com/" class="share-btn"
                        style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white;"><i
                            data-lucide="instagram"></i> Instagram</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                        class="share-btn btn-facebook"><i data-lucide="facebook"></i> Facebook</a>
                </div>
            </aside>
        </div>
    </div>

    <?php if ($recommended): ?>
        <section class="recommended">
            <div class="container">
                <h3 style="font-size: 2rem; font-weight: 800;">Okumaya Devam Edin</h3>
                <div class="rec-grid">
                    <?php foreach ($recommended as $rec): ?>
                        <a href="post_detail.php?slug=<?php echo $rec['slug']; ?>" class="rec-card">
                            <img src="uploads/<?php echo $rec['image']; ?>" class="rec-img">
                            <div style="padding: 1.5rem;">
                                <h4 style="font-size: 1.25rem; font-weight: 700; line-height: 1.3; margin-bottom: 1rem;">
                                    <?php echo $rec['title']; ?>
                                </h4>
                                <span
                                    style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase;">Daha
                                    Fazla <i data-lucide="arrow-right" size="14"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <footer style="padding: 4rem 0; text-align: center; border-top: 1px solid var(--border);">
        <p style="color: var(--gray); font-size: 0.9375rem;">
            <?php echo htmlspecialchars(getSetting('site_description')); ?>. Okuduğunuz
            için teşekkürler.
        </p>
    </footer>

    <script>
        lucide.createIcons();

        // Reading Progress Logic
        window.onscroll = function () {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("readingProgress").style.width = scrolled + "%";
        };
    </script>
</body>

</html>