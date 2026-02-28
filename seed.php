<?php
// seed.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Kategoriler
$cats = ['Teknoloji', 'Yaşam Tarzı', 'Yazılım', 'Seyahat'];
foreach ($cats as $c) {
    $slug = slugify($c);
    $stmt = $db->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (:name, :slug)");
    $stmt->execute(['name' => $c, 'slug' => $slug]);
}

// Örnek Yazılar
$posts = [
    [
        'title' => 'Yapay Zeka Dünyasında Yeni Bir Dönem',
        'preview' => 'AI teknolojilerinin günlük hayatımıza etkisi ve gelecek projeksiyonları.',
        'content' => 'Yapay zeka artık bir lüks değil, gereklilik haline geldi. Bu yazıda GPT-5 ve LLM modellerinin geleceğini tartışıyoruz...',
        'cat' => 1,
        'featured' => 1
    ],
    [
        'title' => 'Minimalist Yaşam Rehberi',
        'preview' => 'Az çoktur felsefesiyle hayatınızı nasıl sadeleştirebilirsiniz?',
        'content' => 'Minimalizm sadece eşya elemek değildir. Zihinsel bir arınma sürecidir...',
        'cat' => 2,
        'featured' => 0
    ],
    [
        'title' => 'PHP 8.3 ile Gelen Yenilikler',
        'preview' => 'Modern PHP dünyasında sizi neler bekliyor?',
        'content' => 'Readonly sınıflarından, dinamik class constant fetch özelliklerine kadar her şey...',
        'cat' => 3,
        'featured' => 0
    ]
];

foreach ($posts as $p) {
    $slug = slugify($p['title']);
    $stmt = $db->prepare("INSERT IGNORE INTO posts (title, preview_text, content, slug, category_id, is_featured, status) VALUES (:t, :p, :c, :s, :cat, :f, 'published')");
    $stmt->execute([
        't' => $p['title'],
        'p' => $p['preview'],
        'c' => $p['content'],
        's' => $slug,
        'cat' => $p['cat'],
        'f' => $p['featured']
    ]);
}

echo "Örnek veriler başarıyla yüklendi!";
