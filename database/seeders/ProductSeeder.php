<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories first
        $minuman = Category::updateOrCreate(
            ['name' => 'Minuman'],
            ['description' => 'Minuman dingin dan panas']
        );

        $makanan = Category::updateOrCreate(
            ['name' => 'Makanan'],
            ['description' => 'Makanan berat dan ringan']
        );

        // Products list
        $products = [
            [
                'name'        => 'Coffee Latte',
                'category_id' => $minuman->id,
                'price'       => 15000,
                'stock'       => 100,
                'image_url'   => null,
            ],
            [
                'name'        => 'Matcha',
                'category_id' => $minuman->id,
                'price'       => 17000,
                'stock'       => 100,
                'image_url'   => null,
            ],
            [
                'name'        => 'Nasi Goreng Seafood',
                'category_id' => $makanan->id,
                'price'       => 25000,
                'stock'       => 50,
                'image_url'   => null,
            ],
            [
                'name'        => 'Roti Bakar',
                'category_id' => $makanan->id,
                'price'       => 12000,
                'stock'       => 50,
                'image_url'   => null,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }

        $this->command->info('Products & Categories created!');
        $this->command->table(
            ['Nama Produk', 'Kategori', 'Harga', 'Stok'],
            [
                ['Coffee Latte',       'Minuman', 'Rp 15.000', 100],
                ['Matcha',            'Minuman', 'Rp 17.000', 100],
                ['Nasi Goreng Seafood', 'Makanan', 'Rp 25.000', 50],
                ['Roti Bakar',         'Makanan', 'Rp 12.000', 50],
            ]
        );
    }
}
