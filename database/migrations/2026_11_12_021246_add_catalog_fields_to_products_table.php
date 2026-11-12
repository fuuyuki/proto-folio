<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug', 120)->nullable();
            $table->string('summary', 280)->nullable()->after('description');
            $table->string('image_alt')->nullable()->after('image_path');
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_price')->default(true);
        });

        $this->backfillSlugs();

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug', 120)->nullable(false)->change();
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['is_published']);
            $table->dropColumn([
                'slug',
                'summary',
                'image_alt',
                'image_width',
                'image_height',
                'sort_order',
                'is_published',
                'is_featured',
                'show_price',
            ]);
        });
    }

    /**
     * Give every existing product a unique public slug.
     *
     * Slugs are generated once and never regenerated, so they stay stable for
     * links and search results even if a product is renamed later.
     */
    private function backfillSlugs(): void
    {
        $used = [];

        DB::table('products')
            ->select('id', 'name')
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$used): void {
                foreach ($products as $product) {
                    $slug = $this->uniqueSlug((string) $product->name, $used);
                    $used[$slug] = true;

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['slug' => $slug]);
                }
            });
    }

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueSlug(string $name, array $used): string
    {
        $base = Str::limit(Str::slug($name), 110, '');
        $base = $base !== '' ? $base : 'product';

        $slug = $base;
        $suffix = 2;

        while (array_key_exists($slug, $used)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
};
