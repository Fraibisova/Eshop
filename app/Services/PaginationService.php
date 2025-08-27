<?php

namespace App\Services;

class PaginationService
{
    public function calculatePagination(int $totalItems, int $itemsPerPage, int $currentPage = 1): array
    {
        $currentPage = max($currentPage, 1);
        $offset = ($currentPage - 1) * $itemsPerPage;
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        return [
            'offset' => $offset,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'hasNext' => $currentPage < $totalPages,
            'hasPrevious' => $currentPage > 1,
            'nextPage' => min($currentPage + 1, $totalPages),
            'previousPage' => max($currentPage - 1, 1)
        ];
    }

    public function renderPagination(int $totalPages, int $currentPage, string $baseUrl = '?', array $getParams = []): string
    {
        if ($totalPages <= 1) return '';
        
        $html = '<div class="pagination">';
        
        if ($currentPage > 1) {
            $params = array_merge($getParams, ['page' => $currentPage - 1]);
            $url = $baseUrl . http_build_query($params);
            $html .= '<a href="' . htmlspecialchars($url) . '" class="pagination-prev">Previous</a>';
        }
        
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $params = array_merge($getParams, ['page' => $i]);
            $url = $baseUrl . http_build_query($params);
            $activeClass = ($i === $currentPage) ? 'active' : '';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="pagination-page ' . $activeClass . '">' . $i . '</a>';
        }
        
        if ($currentPage < $totalPages) {
            $params = array_merge($getParams, ['page' => $currentPage + 1]);
            $url = $baseUrl . http_build_query($params);
            $html .= '<a href="' . htmlspecialchars($url) . '" class="pagination-next">Next</a>';
        }
        
        $html .= '</div>';
        return $html;
    }
}