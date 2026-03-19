<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Banner;
use App\Models\Product;
use App\Models\Collection;
use App\Models\ProductVariant;
use Intervention\Image\ImageManager;

class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize-existing';

    protected $description = 'Varre o banco de dados e converte TODAS as imagens (incluindo variantes e galerias) para WebP.';

    protected ImageManager $manager;

    public function __construct()
    {
        parent::__construct();
        // Inicializa o Intervention Image (Sintaxe v2)
        $this->manager = new ImageManager(['driver' => 'gd']);
    }

    public function handle()
    {
        $this->info('Iniciando a otimização de imagens (incluindo galerias e variantes)...');

        $this->optimizeBanners();
        $this->optimizeCollections();
        $this->optimizeProducts();
        $this->optimizeProductVariants();

        $this->info('✅ Otimização total concluída com sucesso!');
    }

    private function processSingleImage(?string $path): ?string
    {
        if (!$path || str_ends_with(strtolower($path), '.webp') || !Storage::disk('public')->exists($path)) {
            return $path;
        }

        try {
            $absolutePath = Storage::disk('public')->path($path);
            $image = $this->manager->make($absolutePath);

            if ($image->width() > 1920) {
                $image->resize(1920, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $pathInfo = pathinfo($path);
            $newRelativePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            $newAbsolutePath = Storage::disk('public')->path($newRelativePath);

            $image->encode('webp', 80)->save($newAbsolutePath);

            @Storage::disk('public')->delete($path);

            return $newRelativePath;

        } catch (\Exception $e) {
            $this->error("Erro ao processar a imagem {$path}: " . $e->getMessage());
            return $path;
        }
    }

    private function optimizeBanners()
    {
        $banners = Banner::all();
        $this->warn("Otimizando {$banners->count()} Banners...");
        $bar = $this->output->createProgressBar($banners->count());

        foreach ($banners as $banner) {
            $newPath = $this->processSingleImage($banner->image_url);
            if ($newPath !== $banner->image_url) {
                $banner->update(['image_url' => $newPath]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function optimizeCollections()
    {
        $collections = Collection::all();
        $this->warn("Otimizando {$collections->count()} Coleções...");
        $bar = $this->output->createProgressBar($collections->count());

        foreach ($collections as $collection) {
            $newPath = $this->processSingleImage($collection->image_url);
            if ($newPath !== $collection->image_url) {
                $collection->update(['image_url' => $newPath]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function optimizeProducts()
    {
        $products = Product::all();
        $this->warn("Otimizando {$products->count()} Produtos Principais (Capas e Galerias)...");
        $bar = $this->output->createProgressBar($products->count());

        foreach ($products as $product) {
            $updateData = [];

            $newCoverPath = $this->processSingleImage($product->image_url);
            if ($newCoverPath !== $product->image_url) {
                $updateData['image_url'] = $newCoverPath;
            }
            
            $gallery = $product->gallery;
            if (is_string($gallery)) $gallery = json_decode($gallery, true);

            if (is_array($gallery)) {
                $newGallery = [];
                $galleryUpdated = false;
                
                foreach ($gallery as $img) {
                    $newImg = $this->processSingleImage($img);
                    $newGallery[] = $newImg;
                    if ($newImg !== $img) {
                        $galleryUpdated = true;
                    }
                }

                if ($galleryUpdated) {
                    $updateData['gallery'] = $newGallery;
                }
            }

            if (!empty($updateData)) {
                $product->update($updateData);
            }
            
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function optimizeProductVariants()
    {
        // FIX: Added with('product') to prevent the LazyLoadingViolationException
        $variants = ProductVariant::with('product')->get();
        
        $this->warn("Otimizando {$variants->count()} Variantes (Imagens Específicas e Galerias)...");
        $bar = $this->output->createProgressBar($variants->count());

        foreach ($variants as $variant) {
            $updateData = [];

            $newImagePath = $this->processSingleImage($variant->image);
            if ($newImagePath !== $variant->image) {
                $updateData['image'] = $newImagePath;
            }

            $images = $variant->images;
            if (is_string($images)) $images = json_decode($images, true);

            if (is_array($images)) {
                $newImagesGallery = [];
                $imagesUpdated = false;

                foreach ($images as $img) {
                    $newImg = $this->processSingleImage($img);
                    $newImagesGallery[] = $newImg;
                    if ($newImg !== $img) {
                        $imagesUpdated = true;
                    }
                }

                if ($imagesUpdated) {
                    $updateData['images'] = $newImagesGallery;
                }
            }

            if (!empty($updateData)) {
                $variant->update($updateData);
            }

            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }
}