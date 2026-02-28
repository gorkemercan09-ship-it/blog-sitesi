<?php
// index.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Veritabanına bağlanılamadı. Lütfen sunucu (Vercel) ayarlarınızı (Environment Variables) kontrol edin.");
}

logTraffic($db, 'Home Page');

// Kategoriler
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Öne Çıkan Yazılar (Hero)
$featured_posts = $db->query("SELECT p.*, c.name as category_name, a.full_name as author_name, a.profile_image as author_image 
                              FROM posts p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              LEFT JOIN admins a ON p.author_id = a.id 
                              WHERE p.status = 'published' AND p.is_featured = 1 
                              ORDER BY p.created_at DESC LIMIT 3")->fetchAll();

// En Popüler Yazılar (Sidebar)
$popular_posts = $db->query("SELECT p.*, c.name as category_name, a.full_name as author_name 
                             FROM posts p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             LEFT JOIN admins a ON p.author_id = a.id 
                             WHERE p.status = 'published' 
                             ORDER BY p.views_count DESC LIMIT 5")->fetchAll();

// Son Yazılar (Feed)
$category_filter = isset($_GET['category']) ? (int) $_GET['category'] : null;
if ($category_filter) {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, a.full_name as author_name, a.profile_image as author_image 
                          FROM posts p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          LEFT JOIN admins a ON p.author_id = a.id 
                          WHERE p.status = 'published' AND p.category_id = :cat 
                          ORDER BY p.created_at DESC");
    $stmt->bindParam(':cat', $category_filter);
    $stmt->execute();
    $feed_posts = $stmt->fetchAll();
} else {
    $feed_posts = $db->query("SELECT p.*, c.name as category_name, a.full_name as author_name, a.profile_image as author_image 
                                FROM posts p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                LEFT JOIN admins a ON p.author_id = a.id 
                                WHERE p.status = 'published' AND p.is_featured = 0 
                                ORDER BY p.created_at DESC")->fetchAll();
}

// Widget İçin En Son Eklenenler (Tarih sırasına göre ilk 5, feed'den bağımsız)
$sidebar_latest = $db->query("SELECT p.*, c.name as category_name FROM posts p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.status = 'published' 
                             ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

$site_title = getSetting('site_title') ?: 'Antigravity Blog';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo generateSeoTags(); ?>
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
            --accent: #fb7185;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: #fafafa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Navbar */
        nav {
            padding: 1rem 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: var(--primary);
        }

        /* Hero Section */
        .hero-section {
            padding: 2rem 0 4rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            height: 500px;
        }

        .featured-main {
            position: relative;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .featured-side {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 1.5rem;
        }

        .featured-item {
            position: relative;
            border-radius: 1.5rem;
            overflow: hidden;
            cursor: pointer;
        }

        .featured-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .featured-item:hover .featured-img {
            transform: scale(1.05);
        }

        .featured-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9), transparent);
            color: white;
            z-index: 10;
        }

        .featured-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary);
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
        }

        /* Layout Main & Sidebar */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 3rem;
            margin-bottom: 5rem;
        }

        /* Post Cards */
        .post-list {
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        .post-row {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            background: var(--white);
            padding: 1rem;
            border-radius: 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .post-row:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .post-row-img {
            width: 100%;
            height: 200px;
            border-radius: 1rem;
            object-fit: cover;
        }

        .post-row-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Sidebar Items Hover */
        .sidebar a:hover {
            background: var(--light);
        }

        .compact-post-item {
            display: grid;
            grid-template-columns: 50px 1fr;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--dark);
            padding: 0.5rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .compact-post-item:hover {
            background: var(--light);
            transform: translateX(4px);
        }

        .compact-post-img {
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .newsletter-input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: none;
            margin: 1rem 0;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-subscribe {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .hero-grid {
                grid-template-columns: 1fr;
                height: auto;
            }

            .featured-side {
                display: none;
            }

            .featured-main {
                height: 400px;
            }

            .post-row {
                grid-template-columns: 1fr;
            }

            .post-row-img {
                height: 250px;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div class="container nav-content">
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

    <?php if (!$category_filter && count($featured_posts) >= 1): ?>
        <section class="hero-section">
            <div class="container hero-grid">
                <a href="post_detail.php?slug=<?php echo $featured_posts[0]['slug']; ?>"
                    class="featured-main featured-item">
                    <img src="uploads/<?php echo $featured_posts[0]['image']; ?>" class="featured-img">
                    <div class="featured-overlay">
                        <span class="featured-badge"><?php echo $featured_posts[0]['category_name']; ?></span>
                        <h2 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem;">
                            <?php echo $featured_posts[0]['title']; ?>
                        </h2>
                        <p style="opacity: 0.9;"><?php echo $featured_posts[0]['preview_text']; ?></p>
                        <div
                            style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; opacity: 0.8;">
                            <i data-lucide="user" size="14"></i>
                            <?php echo htmlspecialchars($featured_posts[0]['author_name'] ?: 'Yazar'); ?>
                        </div>
                    </div>
                </a>

                <div class="featured-side">
                    <?php for ($i = 1; $i < count($featured_posts); $i++): ?>
                        <a href="post_detail.php?slug=<?php echo $featured_posts[$i]['slug']; ?>" class="featured-item">
                            <img src="uploads/<?php echo $featured_posts[$i]['image']; ?>" class="featured-img">
                            <div class="featured-overlay" style="padding: 1.25rem;">
                                <span class="featured-badge"
                                    style="background: var(--accent);"><?php echo $featured_posts[$i]['category_name']; ?></span>
                                <h3 style="font-size: 1.125rem; font-weight: 700;"><?php echo $featured_posts[$i]['title']; ?>
                                </h3>
                            </div>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <main class="container main-layout">
        <!-- Content Area -->
        <div class="content-area">
            <div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 1.5rem; font-weight: 800;">
                    <?php echo $category_filter ? 'Kategori Yazıları' : 'En Son Eklenenler'; ?>
                </h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="index.php" class="cat-item <?php echo !$category_filter ? 'active' : ''; ?>"
                        style="font-size: 0.875rem; padding: 0.4rem 1rem; border-radius: 2rem; border: 1px solid var(--border); text-decoration: none; color: var(--dark); <?php echo !$category_filter ? 'background: var(--dark); color: white;' : ''; ?>">Hepsi</a>
                </div>
            </div>

            <div class="post-list">
                <?php foreach ($feed_posts as $post): ?>
                    <div class="post-row">
                        <a href="post_detail.php?slug=<?php echo $post['slug']; ?>">
                            <img src="uploads/<?php echo $post['image']; ?>" class="post-row-img">
                        </a>
                        <div class="post-row-content">
                            <span
                                style="color: var(--primary); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem;">
                                <?php echo $post['category_name']; ?>
                            </span>
                            <a href="post_detail.php?slug=<?php echo $post['slug']; ?>"
                                style="text-decoration: none; color: var(--dark);">
                                <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.75rem; line-height: 1.3;">
                                    <?php echo $post['title']; ?>
                                </h3>
                            </a>
                            <p
                                style="color: var(--gray); font-size: 0.9375rem; margin-bottom: 1.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo $post['preview_text']; ?>
                            </p>
                            <div
                                style="display: flex; gap: 1.25rem; align-items: center; font-size: 0.8125rem; color: var(--gray);">
                                <span style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php if ($post['author_image']): ?>
                                        <img src="uploads/<?php echo $post['author_image']; ?>"
                                            style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <i data-lucide="user" size="14"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($post['author_name'] ?: 'Yazar'); ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 0.25rem;"><i data-lucide="calendar"
                                        size="14"></i> <?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                                <span style="display: flex; align-items: center; gap: 0.25rem;"><i data-lucide="eye"
                                        size="14"></i> <?php echo $post['views_count']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($feed_posts)): ?>
                    <div
                        style="text-align: center; padding: 4rem; background: white; border-radius: 1.5rem; border: 1px dotted var(--border);">
                        <i data-lucide="frown" size="48" style="color: var(--gray); margin-bottom: 1rem;"></i>
                        <p style="color: var(--gray);">Bu kategoride henüz yazı bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Area -->
        <aside class="sidebar">
            <!-- Search Widget Mockup -->
            <div class="widget">
                <div style="position: relative;">
                    <input type="text" placeholder="Blogda ara..."
                        style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 0.75rem; border: 1px solid var(--border); font-family: inherit;">
                    <i data-lucide="search" size="18"
                        style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                </div>
            </div>

            <!-- Categories Widget -->
            <div class="widget">
                <h4 class="widget-title"><i data-lucide="layers" size="18"></i> Kategoriler</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?php echo $cat['id']; ?>"
                            style="display: flex; justify-content: space-between; text-decoration: none; color: var(--dark); padding: 0.5rem; border-radius: 0.5rem; transition: background 0.2s;">
                            <span><?php echo $cat['name']; ?></span>
                            <i data-lucide="chevron-right" size="14" style="color: var(--gray);"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Latest Posts Widget -->
            <div class="widget">
                <h4 class="widget-title"><i data-lucide="clock" size="18"></i> Son Yazılar</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($sidebar_latest as $latest): ?>
                        <a href="post_detail.php?slug=<?php echo $latest['slug']; ?>" class="compact-post-item">
                            <img src="uploads/<?php echo $latest['image']; ?>" class="compact-post-img">
                            <div>
                                <h5
                                    style="font-size: 0.875rem; font-weight: 600; line-height: 1.2; margin-bottom: 0.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo $latest['title']; ?>
                                </h5>
                                <span
                                    style="font-size: 0.75rem; color: var(--gray);"><?php echo date('d.m.Y', strtotime($latest['created_at'])); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Popular Posts Widget -->
            <div class="widget">
                <h4 class="widget-title"><i data-lucide="trending-up" size="18"></i> En Çok Okunanlar</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($popular_posts as $pop): ?>
                        <a href="post_detail.php?slug=<?php echo $pop['slug']; ?>" class="compact-post-item">
                            <img src="uploads/<?php echo $pop['image']; ?>" class="compact-post-img">
                            <div>
                                <h5
                                    style="font-size: 0.875rem; font-weight: 600; line-height: 1.2; margin-bottom: 0.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo $pop['title']; ?>
                                </h5>
                                <span
                                    style="font-size: 0.75rem; color: var(--primary); font-weight: 600;"><?php echo $pop['views_count']; ?>
                                    izlenme</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </main>

    <footer style="background: var(--white); border-top: 1px solid var(--border); padding: 4rem 0;">
        <div class="container"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem;">
            <div>
                <a href="index.php" class="logo" style="margin-bottom: 1.5rem;"><i data-lucide="zap"></i>
                    <?php
                    $title_parts = explode(' ', $site_title, 2);
                    echo htmlspecialchars($title_parts[0]);
                    if (isset($title_parts[1]))
                        echo "<span>" . htmlspecialchars($title_parts[1]) . "</span>";
                    ?></a>
                <p style="color: var(--gray); font-size: 0.9375rem;">
                    <?php echo htmlspecialchars(getSetting('site_description')); ?></p>
            </div>
            <div>
                <h5 style="font-weight: 700; margin-bottom: 1.5rem;">Hızlı Bağlantılar</h5>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="index.php" style="color: var(--gray); text-decoration: none;">Ana Sayfa</a></li>
                </ul>
            </div>
            <div>
                <h5 style="font-weight: 700; margin-bottom: 1.5rem;">Kategoriler</h5>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                        <a href="index.php?category=<?php echo $cat['id']; ?>"
                            style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; background: var(--light); color: var(--gray); text-decoration: none; font-size: 0.8125rem;">
                            <?php echo $cat['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="container"
            style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center; color: var(--gray); font-size: 0.875rem;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>. Tüm Hakları
                Saklıdır.</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>