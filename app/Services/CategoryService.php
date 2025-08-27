<?php

namespace App\Services;

class CategoryService
{
    public function translateCategory(string $category): string
    {
        return match ($category) {
            'candle' => 'Svíčky',
            'jewellery' => 'Šperky', 
            'sale' => 'Akce',
            default => 'Doporučujeme'
        };
    }
}