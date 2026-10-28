<?php

use App\Models\ProductCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the categories index lists categories', function () {
    ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Drinks')
        );
});

test('a category can be created with a generated slug', function () {
    $this->post(route('categories.store'), [
        'name' => 'Hot Drinks',
        'sort_order' => 2,
        'is_active' => '1',
    ])->assertRedirect(route('categories.index'));

    $category = ProductCategory::query()->where('name', 'Hot Drinks')->first();

    expect($category?->slug)->toBe('hot-drinks')
        ->and($category?->sort_order)->toBe(2)
        ->and($category?->is_active)->toBeTrue();
});

test('a category can be nested under a parent', function () {
    $parent = ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->post(route('categories.store'), [
        'name' => 'Coffee',
        'parent_id' => $parent->id,
        'sort_order' => 0,
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect(ProductCategory::query()->where('name', 'Coffee')->first()?->parent_id)
        ->toBe($parent->id);
});

test('a category requires a name', function () {
    $this->post(route('categories.store'), ['name' => ''])
        ->assertSessionHasErrors(['name', 'slug']);
});

test('a category slug must be unique', function () {
    ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->post(route('categories.store'), ['name' => 'Drinks'])
        ->assertSessionHasErrors('slug');
});

test('a category cannot be its own parent', function () {
    $category = ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->put(route('categories.update', $category), [
        'name' => 'Drinks',
        'slug' => 'drinks',
        'parent_id' => $category->id,
        'sort_order' => 0,
        'is_active' => '1',
    ])->assertSessionHasErrors('parent_id');
});

test('the edit page exposes the category', function () {
    $category = ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->get(route('categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('categories/Edit')
            ->where('category.id', $category->id)
        );
});

test('a category can be updated', function () {
    $category = ProductCategory::query()->create(['name' => 'Old', 'slug' => 'old']);

    $this->put(route('categories.update', $category), [
        'name' => 'New',
        'slug' => 'new',
        'sort_order' => 5,
        'is_active' => '0',
    ])->assertRedirect(route('categories.index'));

    expect($category->refresh())
        ->name->toBe('New')
        ->slug->toBe('new')
        ->sort_order->toBe(5)
        ->is_active->toBeFalse();
});

test('a category can be deleted', function () {
    $category = ProductCategory::query()->create(['name' => 'Drinks', 'slug' => 'drinks']);

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect($category->fresh())->toBeNull();
});
