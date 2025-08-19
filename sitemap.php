<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    exit();
}

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

include_once 'config.php';

$base_url = 'https://touchthemagic.com';
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
$xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

$xml .= '    <url>' . PHP_EOL;
$xml .= "        <loc>{$base_url}</loc>" . PHP_EOL;
$xml .= '        <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
$xml .= '        <changefreq>daily</changefreq>' . PHP_EOL;
$xml .= '        <priority>1.0</priority>' . PHP_EOL;
$xml .= '    </url>' . PHP_EOL;

$static_pages = [
    '/o-nas' => 'monthly',
    '/kontakty' => 'monthly',
    '/obchodni-podminky' => 'yearly',
    '/reklamace' => 'yearly',
];

foreach ($static_pages as $path => $freq) {
    $xml .= '    <url>' . PHP_EOL;
    $xml .= "        <loc>{$base_url}{$path}</loc>" . PHP_EOL;
    $xml .= '        <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
    $xml .= "        <changefreq>{$freq}</changefreq>" . PHP_EOL;
    $xml .= '        <priority>0.8</priority>' . PHP_EOL;
    $xml .= '    </url>' . PHP_EOL;
}

try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM items WHERE visible = 1 ORDER BY category");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = htmlspecialchars($row['category']);
        $slug = urlencode($category);
        $xml .= '    <url>' . PHP_EOL;
        $xml .= "        <loc>{$base_url}/kategorie/{$slug}</loc>" . PHP_EOL;
        $xml .= '        <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
        $xml .= '        <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '        <priority>0.8</priority>' . PHP_EOL;
        $xml .= '    </url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    // Kategorie preskoceny
}

try {
    $stmt = $pdo->query("SELECT id, name, image, image_folder FROM items WHERE visible = 1 ORDER BY id");

    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = urlencode($product['name']);
        $xml .= '    <url>' . PHP_EOL;
        $xml .= "        <loc>{$base_url}/produkt/{$slug}</loc>" . PHP_EOL;
        $xml .= '        <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
        $xml .= '        <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '        <priority>0.9</priority>' . PHP_EOL;

        if (!empty($product['image'])) {
            $imagePath = htmlspecialchars($product['image']);
            $xml .= '        <image:image>' . PHP_EOL;
            $xml .= "            <image:loc>{$base_url}/{$imagePath}</image:loc>" . PHP_EOL;
            $xml .= '        </image:image>' . PHP_EOL;
        }

        $xml .= '    </url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    // Produkty preskoceny
}

$xml .= '</urlset>' . PHP_EOL;

file_put_contents('sitemap.xml', $xml);

?>
