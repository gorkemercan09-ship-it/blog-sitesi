<?php
// api/index.php
// This is the Vercel entrypoint router.
// It intercepts requests to Vercel Serverless Functions and routes them 
// to the appropriate PHP file in the project's root directory.

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];

// Clean query string from URI
if (($pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $pos);
}

// 1. Root and basic routes
if ($request_uri === '/' || $request_uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// 2. Admin dashboard index
if ($request_uri === '/admin' || $request_uri === '/admin/') {
    require __DIR__ . '/../admin/index.php';
    exit;
}

// 3. Direct file requests (e.g. /categories.php, /admin/login.php)
// Remove leading slash for local file path checks
$local_path = ltrim($request_uri, '/');
$full_path = __DIR__ . '/../' . $local_path;

if (file_exists($full_path) && is_file($full_path)) {
    // Only serve PHP files through this router
    if (pathinfo($full_path, PATHINFO_EXTENSION) === 'php') {
        require $full_path;
        exit;
    }
}

// 4. Admin category or post fallback without .php extension (some systems do this)
if (file_exists($full_path . '.php')) {
    require $full_path . '.php';
    exit;
}

// 5. If it reaches here, the file was not found
http_response_code(404);
echo "404 Not Found";
exit;
