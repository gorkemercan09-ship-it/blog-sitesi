<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - <?php echo htmlspecialchars(getSetting('site_title') ?: 'Yönetim Paneli'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --primary-color: #e11d48;
            --primary-hover: #be123c;
            --input-bg: #ffffff;
            --error-color: #ef4444;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-card {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header i {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #94a3b8;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            width: 18px;
            height: 18px;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            color: var(--text-color);
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        .error-msg {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="header"
            style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <?php
            $logo = getSetting('site_logo');
            if ($logo) {
                echo '<img src="../uploads/' . htmlspecialchars($logo) . '" alt="Logo" style="max-height: 48px; width: auto; object-fit: contain; margin-bottom: 1rem;">';
            } else {
                echo '<i data-lucide="lock" size="40"></i>';
            }
            ?>
            <h1><?php echo htmlspecialchars(getSetting('site_title') ?: 'Yönetim Paneli'); ?></h1>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                Geçersiz kullanıcı adı veya şifre!
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <div class="input-wrapper">
                    <i data-lucide="user"></i>
                    <input type="text" id="username" name="username" required placeholder="admin">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <div class="input-wrapper">
                    <i data-lucide="key"></i>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit">
                Giriş Yap
                <i data-lucide="arrow-right"></i>
            </button>
        </form>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>