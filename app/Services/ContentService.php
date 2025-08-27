<?php

namespace App\Services;

use PDO;

class ContentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getSectionContent(string $section): string
    {
        try {
            $sql = "SELECT content FROM homepage_content WHERE section = :section LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['section' => $section]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['content'] : '';
        } catch (\PDOException $e) {
            return '';
        }
    }

    public function updateHomepageContent(string $section, string $content): bool
    {
        try {
            $sql = "UPDATE homepage_content SET content = :content WHERE section = :section";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['content' => $content, 'section' => $section]);
        } catch (\PDOException $e) {
            throw new \Exception("Chyba při aktualizaci obsahu: " . $e->getMessage());
        }
    }

    public function getHomepageContent(string $section): string
    {
        return $this->getSectionContent($section);
    }

    public function getAllHomepageContent(): array
    {
        try {
            $sql = "SELECT section, content FROM homepage_content";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\PDOException $e) {
            throw new \Exception("Chyba při načítání obsahu: " . $e->getMessage());
        }
    }

    public function createHomepageSection(string $section, string $content = ''): bool
    {
        try {
            $sql = "INSERT INTO homepage_content (section, content) VALUES (:section, :content) ON DUPLICATE KEY UPDATE content = VALUES(content)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['section' => $section, 'content' => $content]);
        } catch (\PDOException $e) {
            throw new \Exception("Chyba při vytváření sekce: " . $e->getMessage());
        }
    }
}