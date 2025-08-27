<?php

namespace App\Services;

use PDO;
use Exception;

class PageService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getPageBySlug(string $slug): ?array
    {
        if (empty($slug)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        return $page ?: null;
    }

    public function getAllPages(): array
    {
        $stmt = $this->db->query("SELECT * FROM pages ORDER BY title");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPage(array $data): bool
    {
        $sql = "INSERT INTO pages (title, slug, content, meta_description, active, created_at) 
                VALUES (:title, :slug, :content, :meta_description, :active, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content' => $data['content'],
            ':meta_description' => $data['meta_description'] ?? '',
            ':active' => $data['active'] ?? 1
        ]);
    }

    public function updatePage(int $id, array $data): bool
    {
        $sql = "UPDATE pages SET title = :title, slug = :slug, content = :content, 
                meta_description = :meta_description, active = :active 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content' => $data['content'],
            ':meta_description' => $data['meta_description'] ?? '',
            ':active' => $data['active'] ?? 1
        ]);
    }

    public function deletePage(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM pages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getPageById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        return $page ?: null;
    }

    public function createSimplePage(string $title, string $slug, string $content): int
    {
        $sql = "INSERT INTO pages (title, slug, content, created_at) VALUES (:title, :slug, :content, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => $content
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSimplePage(int $id, string $title, string $slug, string $content): bool
    {
        $sql = "UPDATE pages SET title = :title, slug = :slug, content = :content WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'content' => $content
        ]);
    }

    public function getAllPagesOrderedByIdDesc(): array
    {
        $stmt = $this->db->query("SELECT * FROM pages ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}