<?php
// includes/functions.php

function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function checkLogin()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}

// includes/functions.php

function logTraffic($db, $page)
{
    if (!$db)
        return false;

    if (!isset($_SERVER['HTTP_USER_AGENT']))
        return false;

    $agent = $_SERVER['HTTP_USER_AGENT'];

    // Bot / Crawler Filtresi
    $bots = [
        'bot',
        'spider',
        'crawler',
        'googlebot',
        'bingbot',
        'yandexbot',
        'slurp',
        'duckduckbot',
        'baiduspider',
        'facebookexternalhit',
        'twitterbot',
        'rogerbot',
        'linkedinbot',
        'embedly',
        'quora link preview',
        'showyoubot',
        'outbrain',
        'pinterest',
        'slackbot',
        'vkShare',
        'W3C_Validator',
        'viber',
        'whatsapp',
        'telegram'
    ];

    foreach ($bots as $bot) {
        if (stripos($agent, $bot) !== false) {
            return false;
        }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        // Aynı IP ve aynı sayfa için mükerrer kaydı tamamen engelle
        $stmt = $db->prepare("SELECT id FROM traffic WHERE ip_address = :ip AND page_visited = :page LIMIT 1");
        $stmt->execute(['ip' => $ip, 'page' => $page]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO traffic (ip_address, user_agent, page_visited) VALUES (:ip, :agent, :page)");
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':agent', $agent);
        $stmt->bindParam(':page', $page);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}


function getSetting($key)
{
    global $db;
    if (!$db) {
        require_once __DIR__ . '/db.php';
        $database = new Database();
        $db = $database->getConnection();
    }
    if (!$db)
        return '';

    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : '';
    } catch (PDOException $e) {
        return '';
    }
}

function generateSeoTags($title = '', $description = '', $image = '', $type = 'website')
{
    $site_title = getSetting('site_title');
    $site_desc = getSetting('site_description');

    $display_title = $title ? $title . " | " . $site_title : $site_title;
    $display_desc = $description ?: $site_desc;
    $og_image = $image ?: getSetting('og_image');

    $html = '<title>' . $display_title . '</title>' . PHP_EOL;
    $html .= '    <meta name="description" content="' . htmlspecialchars($display_desc) . '">' . PHP_EOL;
    $html .= '    <meta name="keywords" content="' . htmlspecialchars(getSetting('site_keywords')) . '">' . PHP_EOL;

    // Open Graph
    $html .= '    <meta property="og:title" content="' . htmlspecialchars($display_title) . '">' . PHP_EOL;
    $html .= '    <meta property="og:description" content="' . htmlspecialchars($display_desc) . '">' . PHP_EOL;
    $html .= '    <meta property="og:type" content="' . $type . '">' . PHP_EOL;
    $html .= '    <meta property="og:url" content="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . '">' . PHP_EOL;
    if ($og_image) {
        $html .= '    <meta property="og:image" content="' . $og_image . '">' . PHP_EOL;
    }

    // Twitter
    $html .= '    <meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
    $html .= '    <meta name="twitter:title" content="' . htmlspecialchars($display_title) . '">' . PHP_EOL;
    $html .= '    <meta name="twitter:description" content="' . htmlspecialchars($display_desc) . '">' . PHP_EOL;

    // Google Analytics
    $ga_code = getSetting('ga_code');
    if ($ga_code) {
        $html .= '    <!-- Global site tag (gtag.js) - Google Analytics -->' . PHP_EOL;
        $html .= '    <script async src="https://www.googletagmanager.com/gtag/js?id=' . $ga_code . '"></script>' . PHP_EOL;
        $html .= '    <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag("js", new Date()); gtag("config", "' . $ga_code . '");</script>' . PHP_EOL;
    }

    return $html;
}
