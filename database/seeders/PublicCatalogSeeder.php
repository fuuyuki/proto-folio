<?php

namespace Database\Seeders;

use App\Actions\NumberSequences\GenerateSequenceNumber;
use App\Enums\ProductType;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class PublicCatalogSeeder extends Seeder
{
    /**
     * Marketing categories taken from the old Astro markdown catalog.
     *
     * "3D Printing" already exists in ProFolio, so the same row is reused
     * instead of creating a duplicate marketing category.
     *
     * @var list<array{slug: string, name: string, sort_order: int}>
     */
    private const CATEGORIES = [
        ['slug' => 'education-robotics-kit', 'name' => 'Education robotics kit', 'sort_order' => 1],
        ['slug' => 'medication-iot', 'name' => 'Medication IoT', 'sort_order' => 2],
        ['slug' => 'medication-android-app', 'name' => 'Medication Android app', 'sort_order' => 3],
        ['slug' => '3d-printing', 'name' => '3D Printing', 'sort_order' => 4],
        ['slug' => 'management-software', 'name' => 'Management software', 'sort_order' => 5],
    ];

    /**
     * Products taken from the old Astro markdown catalog.
     *
     * Prices were never published on the old site, so they stay at zero with
     * the price hidden: the catalog renders "Contact us" until a real price is
     * entered in the admin.
     *
     * @var list<array{
     *     slug: string,
     *     name: string,
     *     category: string,
     *     summary: string,
     *     type: ProductType,
     *     sort_order: int,
     *     featured?: bool,
     *     image_alt?: string
     * }>
     */
    private const PRODUCTS = [
        [
            'slug' => 'robotics-learning-kit',
            'name' => 'Robotics Learning Kit',
            'category' => 'education-robotics-kit',
            'summary' => 'A hands-on kit for learning hardware, sensors, and code through one buildable robot.',
            'type' => ProductType::Product,
            'sort_order' => 1,
            'featured' => true,
            'image_alt' => 'A robotics kit with a controller, sensors, and a small robot chassis.',
        ],
        [
            'slug' => 'connected-medication',
            'name' => 'Connected Medication',
            'category' => 'medication-iot',
            'summary' => 'A connected medication workflow that links devices, reminders, and the people who manage them.',
            'type' => ProductType::Product,
            'sort_order' => 2,
        ],
        [
            'slug' => 'medication-companion',
            'name' => 'Medication Companion',
            'category' => 'medication-android-app',
            'summary' => 'An Android companion for medication schedules and reminders.',
            'type' => ProductType::Product,
            'sort_order' => 3,
        ],
        [
            'slug' => 'custom-3d-printing',
            'name' => 'Custom 3D Printing',
            'category' => '3d-printing',
            'summary' => 'Functional parts and prototypes printed from your design files.',
            'type' => ProductType::Service,
            'sort_order' => 4,
        ],
        [
            'slug' => 'operations-management',
            'name' => 'Operations Management',
            'category' => 'management-software',
            'summary' => 'Software for managing products, inventory, and the work around them.',
            'type' => ProductType::Product,
            'sort_order' => 5,
        ],
    ];

    /**
     * Import the legacy catalog without ever overwriting later admin edits.
     */
    public function run(GenerateSequenceNumber $generateNumber): void
    {
        if (! NumberSequence::query()->where('key', 'product')->exists()) {
            throw new RuntimeException(
                'The product SKU sequence is missing. Run PosDefaultsSeeder before PublicCatalogSeeder.',
            );
        }

        foreach (self::CATEGORIES as $category) {
            ProductCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'is_published' => true,
                ],
            );
        }

        foreach (self::PRODUCTS as $product) {
            if (Product::query()->where('slug', $product['slug'])->exists()) {
                $this->command?->line("Skipping existing product [{$product['name']}].");

                continue;
            }

            $category = ProductCategory::query()
                ->where('slug', $product['category'])
                ->firstOrFail();

            Product::query()->create([
                'slug' => $product['slug'],
                'name' => $product['name'],
                'sku' => $generateNumber->handle('product'),
                'type' => $product['type'],
                'category_id' => $category->id,
                'summary' => $product['summary'],
                'description' => null,
                'price' => 0,
                'cost_price' => 0,
                'sort_order' => $product['sort_order'],
                'is_published' => true,
                'is_active' => true,
                'is_featured' => $product['featured'] ?? false,
                'show_price' => false,
                'track_stock' => false,
                'image_alt' => $product['image_alt'] ?? null,
            ]);
        }
    }
}
