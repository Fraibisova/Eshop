<?php

namespace App\Services;

use PDO;
use Exception;

class SitemapService
{
    private PDO $db;
    private ProductService $productService;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->productService = new ProductService();
    }

    public function generateSitemap(string $baseUrl = 'https://touchthemagic.com'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

        $xml .= $this->generateUrlEntry($baseUrl, 'daily', '1.0');

        $staticPages = [
            '/o-nas' => 'monthly',
            '/kontakty' => 'monthly',
            '/obchodni-podminky' => 'yearly',
            '/reklamace' => 'yearly',
        ];

        foreach ($staticPages as $path => $frequency) {
            $xml .= $this->generateUrlEntry($baseUrl . $path, $frequency, '0.8');
        }

        $xml .= $this->generateCategoryUrls($baseUrl);

        $xml .= $this->generateProductUrls($baseUrl);

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }

    private function generateUrlEntry(string $url, string $changefreq, string $priority, string $imageUrl = null): string
    {
        $xml = '    <url>' . PHP_EOL;
        $xml .= "        <loc>{$url}</loc>" . PHP_EOL;
        $xml .= '        <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
        $xml .= "        <changefreq>{$changefreq}</changefreq>" . PHP_EOL;
        $xml .= "        <priority>{$priority}</priority>" . PHP_EOL;

        if ($imageUrl) {
            $xml .= '        <image:image>' . PHP_EOL;
            $xml .= "            <image:loc>{$imageUrl}</image:loc>" . PHP_EOL;
            $xml .= '        </image:image>' . PHP_EOL;
        }

        $xml .= '    </url>' . PHP_EOL;
        
        return $xml;
    }

    private function generateCategoryUrls(string $baseUrl): string
    {
        $xml = '';
        
        try {
            $stmt = $this->db->query("SELECT DISTINCT category FROM items WHERE visible = 1 ORDER BY category");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $category = htmlspecialchars($row['category']);
                $slug = urlencode($category);
                $url = "{$baseUrl}/kategorie/{$slug}";
                $xml .= $this->generateUrlEntry($url, 'weekly', '0.8');
            }
        } catch (\PDOException $e) {
            error_log("Sitemap category generation error: " . $e->getMessage());
        }

        return $xml;
    }

    private function generateProductUrls(string $baseUrl): string
    {
        $xml = '';
        
        try {
            $stmt = $this->db->query("SELECT id, name, image, image_folder FROM items WHERE visible = 1 ORDER BY id");

            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $slug = urlencode($product['name']);
                $url = "{$baseUrl}/produkt/{$slug}";
                
                $imageUrl = null;
                if (!empty($product['image'])) {
                    $imageUrl = $baseUrl . '/' . htmlspecialchars($product['image']);
                }
                
                $xml .= $this->generateUrlEntry($url, 'weekly', '0.9', $imageUrl);
            }
        } catch (\PDOException $e) {
            error_log("Sitemap product generation error: " . $e->getMessage());
        }

        return $xml;
    }

    public function saveSitemap(string $content, string $filename = 'sitemap.xml'): bool
    {
        $result = file_put_contents($filename, $content);
        return $result !== false;
    }
}