<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\PosDefaultsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
    $this->seed(PosDefaultsSeeder::class);
});

test('an uploaded product photo is optimised, renamed, and stored', function () {
    $this->post(route('products.store'), [
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'is_active' => '1',
        'image' => UploadedFile::fake()->image('my-private-photo.jpg', 2000, 1500),
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->sole();

    expect($product->image_path)
        ->toStartWith('products/')
        ->toEndWith('.webp')
        ->not->toContain('my-private-photo');

    Storage::disk('public')->assertExists($product->image_path);

    $size = getimagesize(Storage::disk('public')->path($product->image_path));

    expect($size[0])->toBe(1024)
        ->and($size[1])->toBe(768)
        ->and($product->image_url)->toContain('/storage/'.$product->image_path);
});

test('replacing a product photo deletes the previous file', function () {
    $this->post(route('products.store'), [
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'image' => UploadedFile::fake()->image('first.jpg', 400, 400),
    ]);

    $product = Product::query()->sole();
    $originalPath = $product->image_path;

    $this->post(route('products.update', $product), [
        '_method' => 'put',
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'image' => UploadedFile::fake()->image('second.jpg', 400, 400),
    ])->assertRedirect(route('products.index'));

    $product->refresh();

    expect($product->image_path)->not->toBe($originalPath);

    Storage::disk('public')->assertMissing($originalPath);
    Storage::disk('public')->assertExists($product->image_path);
});

test('a product photo can be removed', function () {
    $this->post(route('products.store'), [
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'image' => UploadedFile::fake()->image('photo.jpg', 400, 400),
    ]);

    $product = Product::query()->sole();
    $path = $product->image_path;

    $this->post(route('products.update', $product), [
        '_method' => 'put',
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'remove_image' => '1',
    ])->assertRedirect(route('products.index'));

    $product->refresh();

    expect($product->image_path)->toBeNull();

    Storage::disk('public')->assertMissing($path);
});

test('deleting a product removes its photo', function () {
    $this->post(route('products.store'), [
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'image' => UploadedFile::fake()->image('photo.jpg', 400, 400),
    ]);

    $product = Product::query()->sole();
    $path = $product->image_path;

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    Storage::disk('public')->assertMissing($path);

    expect(Product::query()->count())->toBe(0);
});

test('non image uploads are rejected', function () {
    $this->post(route('products.store'), [
        'name' => 'Cold Brew',
        'type' => 'product',
        'price' => '18.00',
        'image' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
    ])->assertSessionHasErrors('image');

    expect(Product::query()->count())->toBe(0);
});
