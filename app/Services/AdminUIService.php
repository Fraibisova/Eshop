<?php

namespace App\Services;

class AdminUIService
{
    public function renderHeader(): void
    {
        echo '<nav>
            <a href="/admin/dashboard">Domů</a>
            <a href="/admin/newsletter">Newsletter</a>
            <a href="/admin/website">Editovat web</a>
            <a href="/admin/pages">Editovat stránky</a>
            <a href="/admin/products/add">Přidat zboží</a>
            <a href="/admin/products">Upravit zboží</a>
            <a href="/admin/upload">Nahrát fotky</a>
            <a href="/admin/orders">Objednávky</a>
            <a href="/">Přejít na web</a>
            <a href="/auth/logout">Odhlásit se</a>
        </nav>';
    }

    public function renderStatusFilter(string $currentStatus = ''): string
    {
        $statuses = ['draft', 'scheduled', 'sent', 'sending'];
        
        $html = '<div class="status-filter">';
        $html .= '<a href="?" class="filter-link' . (empty($currentStatus) ? ' active' : '') . '">Všechny</a>';
        
        foreach ($statuses as $status) {
            $isActive = ($currentStatus === $status) ? ' active' : '';
            $html .= '<a href="?status=' . urlencode($status) . '" class="filter-link' . $isActive . '">' . ucfirst($status) . '</a>';
        }
        
        $html .= '</div>';
        return $html;
    }

    public function renderFilterForm(array $columns, array $getParams = []): string
    {
        $html = '<form method="GET" class="filter-form">';
        
        foreach ($columns as $column) {
            $value = isset($getParams[$column]) ? htmlspecialchars($getParams[$column]) : '';
            $label = ucfirst(str_replace('_', ' ', $column));
            
            $html .= '<div class="form-group">';
            $html .= '<label for="' . $column . '">' . $label . ':</label>';
            $html .= '<input type="text" name="' . $column . '" value="' . $value . '" id="' . $column . '" class="form-control">';
            $html .= '</div>';
        }
        
        $html .= '<button type="submit" class="btn btn-primary">Filtrovat</button>';
        $html .= '<a href="?" class="btn btn-secondary">Reset</a>';
        $html .= '</form>';
        
        return $html;
    }

    public function renderBreadcrumb(array $items): string
    {
        if (empty($items)) return '';
        
        $html = '<nav class="breadcrumb">';
        
        foreach ($items as $index => $item) {
            $isLast = ($index === count($items) - 1);
            
            if ($isLast) {
                $html .= '<span class="breadcrumb-current">' . htmlspecialchars($item['title']) . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="breadcrumb-link">' . htmlspecialchars($item['title']) . '</a>';
                $html .= '<span class="breadcrumb-separator">></span>';
            }
        }
        
        $html .= '</nav>';
        return $html;
    }
}