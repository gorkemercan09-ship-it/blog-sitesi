<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
logTraffic($db, 'Categories Page');

$site_title = getSetting('site_title') ?: 'Antigravity Blog';

// Kategorileri ve her kategorideki yazı sayısını çek
$categories = $db->query("SELECT c.*, COUNT(p.id) as post_count 
                          FROM categories c 
                          LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                          GROUP BY c.id 
                          ORDER BY c.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo generateSeoTags('Kategoriler'); ?>
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
            background-color: #fafafa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

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

        .page-header {
            padding: 4rem 0;
            text-align: center;
            background: linear-gradient(to bottom, #fff, #fafafa);
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            color: var(--gray);
            font-size: 1.125rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 4rem 0;
        }

        .category-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 2rem;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--dark);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }

        .category-icon-wrapper {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: transform 0.4s ease;
        }

        .category-card:hover .category-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .category-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .category-count {
            font-size: 0.875rem;
            color: var(--gray);
            font-weight: 500;
            background: var(--light);
            padding: 0.25rem 1rem;
            border-radius: 2rem;
        }

        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 4rem 0;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="container nav-content">
            <a href="index.php" class="logo"><i data-lucide="zap"></i>
                <?php
                $title_parts = explode(' ', $site_title, 2);
                echo htmlspecialchars($title_parts[0]);
                if (isset($title_parts[1]))
                    echo "<span>" . htmlspecialchars($title_parts[1]) . "</span>";
                ?>
            </a>
            <div style="display: flex; gap: 2rem; align-items: center;">
                <a href="index.php" style="text-decoration: none; color: var(--dark); font-weight: 500;">Ana Sayfa</a>
                <a href="categories.php"
                    style="text-decoration: none; color: var(--primary); font-weight: 700;">Kategoriler</a>
            </div>
        </div>
    </nav>

    <header class="page-header">
        <div class="container">
            <h1 class="page-title">Keşfet</h1>
            <p class="page-subtitle">İlgi alanınıza göre kategorilere göz atın ve en güncel içerikleri hemen keşfedin.
            </p>
        </div>
    </header>

    <main class="container">
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?php echo $cat['id']; ?>" class="category-card">
                    <div class="category-icon-wrapper">
                        <i data-lucide="folder" size="32"></i>
                    </div>
                    <h3 class="category-name">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </h3>
                    <span class="category-count">
                        <?php echo $cat['post_count']; ?> Yazı
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <div class="container"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem;">
            <div>
                <a href="index.php" class="logo" style="margin-bottom: 1.5rem;"><i data-lucide="zap"></i>
                    <?php
                    $title_parts = explode(' ', $site_title, 2);
                    echo htmlspecialchars($title_parts[0]);
                    if (isset($title_parts[1]))
                        echo "<span>" . htmlspecialchars($title_parts[1]) . "</span>";
                    ?>
                </a>
                <p style="color: var(--gray); font-size: 0.9375rem;">
                    <?php echo htmlspecialchars(getSetting('site_description')); ?>
                </p>
            </div>
            <div>
                <h5 style="font-weight: 700; margin-bottom: 1.5rem;">Hızlı Bağlantılar</h5>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="index.php" style="color: var(--gray); text-decoration: none;">Ana Sayfa</a></li>
                    <li><a href="categories.php" style="color: var(--gray); text-decoration: none;">Kategoriler</a></li>
                </ul>
            </div>
        </div>
        <div class="container"
            style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center; color: var(--gray); font-size: 0.875rem;">
            <p>&copy;
                <?php echo date('Y'); ?>
                <?php echo htmlspecialchars($site_title); ?>. Tüm Hakları Saklıdır.
            </p>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>

</html>