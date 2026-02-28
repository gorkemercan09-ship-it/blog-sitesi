<?php
// admin/layout_header.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
checkLogin();

$database = new Database();
$header_db = $database->getConnection();
$header_stmt = $header_db->prepare("SELECT * FROM admins WHERE id = :id");
$header_stmt->execute(['id' => $_SESSION['admin_id']]);
$current_admin = $header_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo ($page_title ?? 'Admin Paneli') . ' - ' . htmlspecialchars(getSetting('site_title') ?: 'Yönetim Paneli'); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-color: #f8fafc;
            --sidebar-bg: #ffffff;
            --main-bg: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --primary: #e11d48;
            --primary-light: #ffe4e6;
            --border: #e2e8f0;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--main-bg);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--text-dark);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            border-right: 1px solid var(--border);
            transition: transform 0.3s ease;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            color: var(--text-dark);
            text-decoration: none;
        }

        .nav-menu {
            list-style: none;
            flex-grow: 1;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-light);
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .notif-item.unread {
            background-color: var(--primary-light);
        }

        /* Profile Dropdown */
        .profile-wrapper {
            position: relative;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 200px;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            margin-top: 0.5rem;
            display: none;
            z-index: 1000;
        }

        .profile-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        .profile-dropdown-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: background-color 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .profile-dropdown-link:hover {
            background-color: var(--bg-color);
            color: var(--primary);
        }

        .profile-dropdown-divider {
            height: 1px;
            background-color: var(--border);
            margin: 0.25rem 0;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-link.active {
            color: var(--primary);
        }

        .nav-link.active i {
            color: var(--primary);
        }

        .logout-btn {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }

        /* Main Content */
        .content {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }

        .user-info:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .menu-toggle {
            display: block;
            background: none;
            border: none;
            color: var(--text-dark);
            cursor: pointer;
            padding: 0.5rem;
            z-index: 101;
        }

        /* Table Responsiveness */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }

        /* Notifications */
        .notif-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            display: none;
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 320px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow: hidden;
        }

        .notif-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .notif-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notif-item {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            transition: background 0.2s;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item:hover {
            background: #f1f5f9;
        }

        .notif-item.unread {
            background: #eff6ff;
        }

        .notif-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .notif-msg {
            color: var(--text-light);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .view-site-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary) !important;
            border-color: var(--primary) !important;
            transform: translateY(-1px);
        }

        .notif-time {
            color: var(--text-light);
            font-size: 0.7rem;
            margin-top: 0.5rem;
            text-align: right;
        }

        /* Sidebar Collapsed State (Desktop) */
        body.sidebar-collapsed .sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-collapsed .content {
            margin-left: 0;
            width: 100%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .top-bar-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .view-site-btn {
                order: 2;
                font-size: 0.75rem !important;
                padding: 0.4rem 0.8rem !important;
            }

            .notif-wrapper {
                order: 1;
            }

            .profile-wrapper {
                order: 3;
            }

            body.sidebar-collapsed .sidebar {
                transform: translateX(-100%);
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar" id="sidebar">
        <a href="index.php" class="sidebar-brand">
            <?php
            $logo = getSetting('site_logo');
            if ($logo) {
                echo '<img src="../uploads/' . htmlspecialchars($logo) . '" alt="Logo" style="max-height: 32px; width: auto; object-fit: contain;">';
            } else {
                echo '<i data-lucide="zap"></i>';
            }
            ?>
            <span
                style="font-size: 1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars(getSetting('site_title') ?: 'Yönetim Paneli'); ?></span>
        </a>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i> Panel
                </a>
            </li>
            <li class="nav-item">
                <a href="posts.php" class="nav-link <?php echo $active_page === 'posts' ? 'active' : ''; ?>">
                    <i data-lucide="file-text"></i> Yazılar
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?php echo $active_page === 'categories' ? 'active' : ''; ?>">
                    <i data-lucide="tag"></i> Kategoriler
                </a>
            </li>
            <li class="nav-item">
                <a href="comments.php" class="nav-link <?php echo $active_page === 'comments' ? 'active' : ''; ?>">
                    <i data-lucide="message-circle"></i> Yorumlar
                </a>
            </li>
            <li class="nav-item">
                <a href="traffic.php" class="nav-link <?php echo $active_page === 'traffic' ? 'active' : ''; ?>">
                    <i data-lucide="bar-chart-3"></i> Trafik
                </a>
            </li>

            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i> Ayarlar
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="auth.php?logout=1" class="nav-link" style="color: #ef4444;">
                <i data-lucide="log-out"></i> Çıkış Yap
            </a>
        </div>
    </aside>

    <main class="content">
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="menu-toggle" id="menu-toggle">
                    <i data-lucide="menu"></i>
                </button>
                <h2 style="font-weight: 700; color: var(--text-dark);">
                    <?php echo $page_title ?? 'Panel'; ?>
                </h2>
            </div>
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <a href="../index.php" target="_blank" class="view-site-btn"
                    style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--text-light); font-size: 0.875rem; font-weight: 500; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border); transition: all 0.2s;">
                    <i data-lucide="external-link" size="16"></i> Siteyi Görüntüle
                </a>

                <div style="position: relative;" id="notif-wrapper">
                    <button id="notif-btn"
                        style="background: none; border: none; color: var(--text-light); cursor: pointer; padding: 0.5rem; position: relative; transition: color 0.2s;">
                        <i data-lucide="bell"></i>
                        <span id="notif-badge" class="notif-badge"></span>
                    </button>

                    <div id="notif-dropdown" class="notif-dropdown">
                        <div class="notif-header">
                            <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: var(--text-dark);">
                                Bildirimler</h3>
                            <button id="notif-clear"
                                style="background: none; border: none; font-size: 0.75rem; color: var(--primary); cursor: pointer; text-decoration: underline;">Tümünü
                                Temizle</button>
                        </div>
                        <div id="notif-list" class="notif-list">
                            <!-- Items generate here -->
                        </div>
                    </div>
                </div>

                <div class="profile-wrapper" id="profile-wrapper">
                    <button id="profile-btn" class="user-info"
                        style="background: none; border: none; cursor: pointer; text-align: left; width: 100%;">
                        <div style="text-align: right;">
                            <p style="font-weight: 600; font-size: 0.875rem;">
                                <?php echo htmlspecialchars($current_admin['full_name'] ?: $current_admin['username']); ?>
                            </p>
                            <p style="font-size: 0.75rem; color: var(--text-light);">Yönetici</p>
                        </div>
                        <div class="user-avatar" style="overflow: hidden;">
                            <?php if ($current_admin['profile_image']): ?>
                                <img src="../uploads/<?php echo $current_admin['profile_image']; ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </button>

                    <div id="profile-dropdown" class="profile-dropdown">
                        <a href="profile.php" class="profile-dropdown-link">
                            <i data-lucide="user" style="width: 18px; height: 18px;"></i> Profil Bilgileri
                        </a>
                        <div class="profile-dropdown-divider"></div>
                        <a href="auth.php?logout=1" class="profile-dropdown-link" style="color: #ef4444;">
                            <i data-lucide="log-out" style="width: 18px; height: 18px;"></i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .welcome-toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                display: flex;
                align-items: center;
                gap: 0.75rem;
                z-index: 9999;
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 1s ease, transform 1s ease;
                font-weight: 500;
            }

            .welcome-toast.show {
                opacity: 1;
                transform: translateY(0);
            }
        </style>

        <?php if (isset($_SESSION['login_welcome']) && $_SESSION['login_welcome'] === true): ?>
            <div id="welcome-toast" class="welcome-toast">
                <i data-lucide="hand"></i>
                Hoş geldin, <?php echo htmlspecialchars($current_admin['full_name'] ?: $current_admin['username']); ?>!
            </div>
            <?php unset($_SESSION['login_welcome']); ?>
        <?php endif; ?>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const welcomeToast = document.getElementById('welcome-toast');
                if (welcomeToast) {
                    // Show toast shortly after load
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            welcomeToast.classList.add('show');
                        });
                    });

                    // Hide and remove toast after 1 second
                    setTimeout(() => {
                        welcomeToast.classList.remove('show');
                        setTimeout(() => welcomeToast.remove(), 1000);
                    }, 1000);
                }

                const menuToggle = document.getElementById('menu-toggle');
                const sidebar = document.getElementById('sidebar');
                const body = document.body;

                if (menuToggle) {
                    menuToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (window.innerWidth <= 768) {
                            sidebar.classList.toggle('open');
                        } else {
                            body.classList.toggle('sidebar-collapsed');
                        }
                        console.log('Toggle clicked');
                    });
                }

                document.addEventListener('click', (e) => {
                    // Sidebar mobile toggle
                    if (window.innerWidth <= 768 && sidebar && menuToggle) {
                        if (sidebar.classList.contains('open') &&
                            !sidebar.contains(e.target) &&
                            !menuToggle.contains(e.target)) {
                            sidebar.classList.remove('open');
                        }
                    }

                    // Profile Dropdown Toggle
                    const profileBtn = document.getElementById('profile-btn');
                    const profileDropdown = document.getElementById('profile-dropdown');
                    const profileWrapper = document.getElementById('profile-wrapper');

                    if (profileBtn && profileDropdown && profileWrapper) {
                        if (profileBtn.contains(e.target)) {
                            profileDropdown.classList.toggle('show');
                        } else if (!profileWrapper.contains(e.target)) {
                            profileDropdown.classList.remove('show');
                        }
                    }
                });

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Notification Logic
                const notifBtn = document.getElementById('notif-btn');
                const notifDropdown = document.getElementById('notif-dropdown');
                const notifBadge = document.getElementById('notif-badge');
                const notifList = document.getElementById('notif-list');
                const notifClear = document.getElementById('notif-clear');
                const notifWrapper = document.getElementById('notif-wrapper');

                let isNotifOpen = false;

                function fetchNotifications() {
                    fetch('api_notifications.php?action=fetch')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (data.unread_count > 0) {
                                    notifBadge.style.display = 'block';
                                } else {
                                    notifBadge.style.display = 'none';
                                }

                                notifList.innerHTML = '';
                                if (data.notifications.length === 0) {
                                    notifList.innerHTML = '<div style="padding: 1.5rem 1rem; text-align: center; color: var(--text-light); font-size: 0.875rem;"><i data-lucide="bell-off" size="24" style="margin-bottom: 0.5rem; opacity: 0.5; display: block; margin-left: auto; margin-right: auto;"></i>Bildirim yok</div>';
                                } else {
                                    data.notifications.forEach(notif => {
                                        const unreadClass = notif.is_read == 0 ? 'unread' : '';
                                        const dateStr = new Date(notif.created_at);
                                        const timeStr = dateStr.toLocaleDateString('tr-TR') + ' ' + dateStr.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });

                                        const itemHtml = `
                                            <a href="${notif.link ? notif.link : '#'}" class="notif-item ${unreadClass}">
                                                <div class="notif-title">${notif.title}</div>
                                                <div class="notif-msg">${notif.message}</div>
                                                <div class="notif-time">${timeStr}</div>
                                            </a>
                                        `;
                                        notifList.insertAdjacentHTML('beforeend', itemHtml);
                                    });
                                }
                                lucide.createIcons();
                            }
                        })
                        .catch(err => console.error(err));
                }

                if (notifBtn) {
                    notifBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        isNotifOpen = !isNotifOpen;
                        notifDropdown.style.display = isNotifOpen ? 'block' : 'none';

                        if (isNotifOpen && notifBadge.style.display === 'block') {
                            fetch('api_notifications.php?action=mark_read', { method: 'POST' })
                                .then(() => {
                                    notifBadge.style.display = 'none';
                                    fetchNotifications();
                                });
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (isNotifOpen && !notifWrapper.contains(e.target)) {
                            isNotifOpen = false;
                            notifDropdown.style.display = 'none';
                        }
                    });

                    notifClear.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (confirm('Tüm bildirimleri silmek istediğinizden emin misiniz?')) {
                            fetch('api_notifications.php?action=clear_all', { method: 'POST' })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) fetchNotifications();
                                });
                        }
                    });

                    fetchNotifications();
                    setInterval(fetchNotifications, 3000); // Check every 3 seconds for real-time feel
                }
            });
        </script>