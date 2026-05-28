<?php
// app/Http/Controllers/ReviewController.php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    /**
     * Valida, autoriza e persiste a avaliação do cliente.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1200',
            'images' => 'nullable|array|max:4',
            // ✏️ alterado: Segurança contra MIME spoofing. Força verificação dupla.
            'images.*' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp|max:2048',
            'removed_images' => 'nullable|json' // Valida a string enviada pelo Alpine
        ]);

        $hasPurchased = auth()->user()->orders()->where('status', 'delivered')
            ->whereHas('items', fn($q) => $q->where('product_id', $product->id))->exists();

        if (!$hasPurchased) {
            return back()->withErrors(['error' => 'Ação não autorizada.']);
        }

        // Recupera a avaliação existente se houver
        $review = Review::where('user_id', auth()->id())->where('product_id', $product->id)->first();
        $currentImages = $review ? ($review->images ?? []) : [];

        // 1. Processa as imagens desanexadas pelo usuário
        if ($request->filled('removed_images') && $review) {
            $toRemove = json_decode($request->input('removed_images'), true) ?? [];
            
            foreach ($toRemove as $path) {
                // Medida de Segurança: Garante que o usuário só pode apagar imagens que pertencem à própria avaliação dele
                if (in_array($path, $currentImages)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    $currentImages = array_values(array_diff($currentImages, [$path]));
                }
            }
        }

        // 2. Anexa novas imagens caso tenham sido enviadas
        if ($request->hasFile('images')) {
            // Calcula o espaço restante para não passar de 4 imagens no total
            $availableSlots = 4 - count($currentImages);
            if ($availableSlots > 0) {
                foreach (array_slice($request->file('images'), 0, $availableSlots) as $image) {
                    $currentImages[] = $image->store('reviews', 'public');
                }
            }
        }

        // Salva ou atualiza a entrada (Upsert)
        Review::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $product->id],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
                'images' => $currentImages
            ]
        );

        Cache::tags(['catalog', 'products'])->flush();

        return back()->with('success', 'Avaliação atualizada com sucesso!');
    }
}